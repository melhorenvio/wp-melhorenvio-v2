<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Hooks;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use MelhorEnvio\Core\Container;
use MelhorEnvio\Hooks\HookManager;
use MelhorEnvio\Http\Controllers\Admin\AdminMenuController;
use MelhorEnvio\Http\Controllers\Admin\ModeNoticeController;
use MelhorEnvio\Http\Controllers\Auth\DisconnectController;
use MelhorEnvio\Http\Controllers\Auth\QuotationTokenController;
use MelhorEnvio\Http\Controllers\Auth\SaveSecretController;
use MelhorEnvio\Http\Controllers\Checkout\CheckoutFieldsController;
use MelhorEnvio\Http\Controllers\Frontend\ProductShippingCalculatorController;
use MelhorEnvio\Http\Controllers\Order\NFeXmlUploadController;
use MelhorEnvio\Http\Controllers\Order\OrderBulkImportController;
use MelhorEnvio\Http\Controllers\Order\OrderBulkImportNoticeController;
use MelhorEnvio\Http\Controllers\Order\OrderInvoiceKeyMetaBoxController;
use MelhorEnvio\Http\Controllers\Order\OrderMetaBackfillController;
use MelhorEnvio\Http\Controllers\Order\OrderNoteInvoiceKeyController;
use MelhorEnvio\Http\Controllers\Quotation\QuotationController;
use MelhorEnvio\Http\Controllers\Settings\GetSettingsController;
use MelhorEnvio\Http\Controllers\Settings\SaveSettingsController;
use MelhorEnvio\Services\Order\ForceImportService;
use MelhorEnvio\Services\Shipping\MelhorEnvioShippingService;
use MelhorEnvio\Services\Webhook\ImmediateWebhookDeliveryService;
use MelhorEnvio\Tests\TestCase;
use Mockery;
use WC_Webhook;

final class HookManagerTest extends TestCase {

	private const ME_WEBHOOK_ID    = 7;
	private const OTHER_WEBHOOK_ID = 8;
	private const ORDER_ID         = 123;
	private const HASH_META        = '_me_webhook_last_hash';

	private const CONTROLLERS = array(
		AdminMenuController::class,
		ModeNoticeController::class,
		SaveSecretController::class,
		QuotationTokenController::class,
		DisconnectController::class,
		GetSettingsController::class,
		SaveSettingsController::class,
		QuotationController::class,
		NFeXmlUploadController::class,
		ProductShippingCalculatorController::class,
		CheckoutFieldsController::class,
		OrderMetaBackfillController::class,
		OrderInvoiceKeyMetaBoxController::class,
		OrderNoteInvoiceKeyController::class,
		OrderBulkImportController::class,
		OrderBulkImportNoticeController::class,
	);

	private const CAPTURED_FILTERS = array(
		'woocommerce_hidden_order_itemmeta',
		'woocommerce_shipping_methods',
		'woocommerce_webhook_should_deliver',
		'woocommerce_webhook_deliver_async',
		'woocommerce_webhook_http_args',
	);

	private const CAPTURED_ACTIONS = array(
		'woocommerce_auth_page_footer',
		'woocommerce_webhook_delivery',
		'shutdown',
	);

	private Container $container;

	private HookManager $manager;

	/** @var array<string, object> */
	private array $controllerSpies = array();

	/** @var array<string, array{callback: callable, priority: int, args: int}> */
	private array $captured = array();

	protected function setUp(): void {
		parent::setUp();

		$this->container = new Container();
		$this->container->singleton( ImmediateWebhookDeliveryService::class, ImmediateWebhookDeliveryService::class );

		foreach ( self::CONTROLLERS as $class ) {
			$spy                             = $this->makeControllerSpy();
			$this->controllerSpies[ $class ] = $spy;
			$this->container->bind(
				$class,
				static function () use ( $spy ) {
					return $spy;
				}
			);
		}

		foreach ( self::CAPTURED_FILTERS as $hook ) {
			Filters\expectAdded( $hook )->once()->whenHappen( $this->captureInto( $hook ) );
		}

		foreach ( self::CAPTURED_ACTIONS as $hook ) {
			Actions\expectAdded( $hook )->once()->whenHappen( $this->captureInto( $hook ) );
		}

		WC_Webhook::$records[ self::ME_WEBHOOK_ID ]    = array(
			'delivery_url' => 'https://webhook-wordpress-envios.melhorenvio.com.br/hook',
			'topic'        => 'order.updated',
		);
		WC_Webhook::$records[ self::OTHER_WEBHOOK_ID ] = array(
			'delivery_url' => 'https://example.com/hook',
			'topic'        => 'order.updated',
		);

		$this->manager = new HookManager( $this->container );
		$this->manager->register();
	}

	public function test_register_registers_every_controller_once(): void {
		foreach ( $this->controllerSpies as $class => $spy ) {
			self::assertSame( 1, $spy->registered, $class . ' was not registered exactly once.' );
		}
	}

	public function test_register_does_not_disable_ssl_verification_site_wide(): void {
		self::assertFalse( has_filter( 'https_ssl_verify' ) );
		self::assertFalse( has_filter( 'https_local_ssl_verify' ) );
	}

	public function test_webhook_hooks_accept_all_arguments(): void {
		self::assertSame( 10, $this->captured['woocommerce_webhook_should_deliver']['priority'] );
		self::assertSame( 3, $this->captured['woocommerce_webhook_should_deliver']['args'] );
		self::assertSame( 10, $this->captured['woocommerce_webhook_deliver_async']['priority'] );
		self::assertSame( 3, $this->captured['woocommerce_webhook_deliver_async']['args'] );
		self::assertSame( 10, $this->captured['woocommerce_webhook_http_args']['priority'] );
		self::assertSame( 3, $this->captured['woocommerce_webhook_http_args']['args'] );
		self::assertSame( 9, $this->captured['shutdown']['priority'] );
		self::assertSame( 10, $this->captured['woocommerce_webhook_delivery']['priority'] );
		self::assertSame( 5, $this->captured['woocommerce_webhook_delivery']['args'] );
	}

	public function test_register_does_not_hook_logout(): void {
		// A assinatura é única para o site: o logout de qualquer usuário não pode apagá-la.
		self::assertFalse( has_action( 'wp_logout' ) );
		self::assertFalse( method_exists( $this->manager, 'onUserLogout' ) );
	}

	public function test_hidden_order_item_meta_appends_melhor_envio_keys(): void {
		$result = $this->invoke( 'woocommerce_hidden_order_itemmeta', array( '_reduced_stock' ) );

		self::assertSame(
			array(
				'_reduced_stock',
				'me_service_id',
				'me_service_name',
				'me_company_id',
				'me_company_name',
				'me_delivery_time',
			),
			$result
		);
	}

	public function test_shipping_methods_registers_melhor_envio_method(): void {
		$result = $this->invoke( 'woocommerce_shipping_methods', array( 'flat_rate' => 'WC_Shipping_Flat_Rate' ) );

		self::assertSame(
			array(
				'flat_rate'    => 'WC_Shipping_Flat_Rate',
				'melhor_envio' => MelhorEnvioShippingService::class,
			),
			$result
		);
	}

	public function test_auth_page_footer_prints_double_click_guard_script(): void {
		$this->expectOutputRegex( '/<script>.*\.wc-auth-approve.*dataset\.clicked.*<\/script>/s' );

		$this->invoke( 'woocommerce_auth_page_footer' );
	}

	public function test_should_deliver_keeps_false_without_checking_order(): void {
		Functions\expect( 'wc_get_order' )->never();

		self::assertFalse( $this->shouldDeliver( false, self::ME_WEBHOOK_ID ) );
	}

	public function test_should_deliver_ignores_third_party_webhooks(): void {
		Functions\expect( 'wc_get_order' )->never();

		self::assertTrue( $this->shouldDeliver( true, self::OTHER_WEBHOOK_ID ) );
	}

	public function test_should_deliver_ignores_other_topics(): void {
		WC_Webhook::$records[ self::ME_WEBHOOK_ID ]['topic'] = 'order.created';
		Functions\expect( 'wc_get_order' )->never();

		self::assertTrue( $this->shouldDeliver( true, self::ME_WEBHOOK_ID ) );
	}

	public function test_should_deliver_blocks_when_order_not_found(): void {
		Functions\expect( 'wc_get_order' )->once()->with( self::ORDER_ID )->andReturn( false );

		self::assertFalse( $this->shouldDeliver( true, self::ME_WEBHOOK_ID ) );
	}

	/**
	 * @dataProvider melhorEnvioDeliveryUrls
	 */
	public function test_should_deliver_when_order_was_never_delivered( string $url ): void {
		WC_Webhook::$records[ self::ME_WEBHOOK_ID ]['delivery_url'] = $url;
		$this->givenOrder( $this->makeOrder() );

		self::assertTrue( $this->shouldDeliver( true, self::ME_WEBHOOK_ID ) );
	}

	public function melhorEnvioDeliveryUrls(): array {
		return array(
			'melhorenvio host'         => array( 'https://webhook-wordpress-envios.melhorenvio.com.br/hook' ),
			'woocommerceenvios host'   => array( 'https://webhook.woocommerceenvios.com/hook' ),
		);
	}

	public function test_should_not_deliver_unchanged_order_after_successful_delivery(): void {
		$order = $this->makeOrder();
		$this->givenOrder( $order );

		$this->deliver( 200, self::ME_WEBHOOK_ID );
		$order->storedHash = $order->savedHash;

		self::assertNotNull( $order->savedHash );
		self::assertFalse( $this->shouldDeliver( true, self::ME_WEBHOOK_ID ) );
	}

	/**
	 * @dataProvider relevantOrderChanges
	 */
	public function test_should_deliver_again_after_relevant_change( array $change ): void {
		$order = $this->makeOrder();
		$this->givenOrder( $order );
		$this->deliver( 200, self::ME_WEBHOOK_ID );
		$order->storedHash = $order->savedHash;

		foreach ( $change as $property => $value ) {
			$order->state[ $property ] = $value;
		}

		self::assertTrue( $this->shouldDeliver( true, self::ME_WEBHOOK_ID ) );
	}

	public function test_should_deliver_again_when_only_invoice_xml_is_replaced(): void {
		$order                       = $this->makeOrder();
		$order->state['invoice_xml'] = '<nfeProc><NFe Id="NFe1"/></nfeProc>';
		$this->givenOrder( $order );
		$this->deliver( 200, self::ME_WEBHOOK_ID );
		$order->storedHash = $order->savedHash;

		$order->state['invoice_xml'] = '<nfeProc><NFe Id="NFe2"/></nfeProc>';

		self::assertTrue( $this->shouldDeliver( true, self::ME_WEBHOOK_ID ) );
	}

	public function test_order_hash_without_invoice_xml_matches_hash_stored_before_xml_was_hashed(): void {
		$order = $this->makeOrder();
		$this->givenOrder( $order );

		$this->deliver( 200, self::ME_WEBHOOK_ID );

		// Fórmula anterior à inclusão do XML: pedidos sem XML não podem ter o hash alterado.
		$legacyHash = md5(
			serialize(
				array(
					'status'         => 'processing',
					'billing'        => array( 'city' => 'São Paulo' ),
					'shipping'       => array( 'postcode' => '01001000' ),
					'shipping_lines' => array(
						5 => array(
							'method_id'    => 'melhor_envio',
							'method_title' => 'PAC',
							'total'        => '15.00',
						),
					),
					'line_items'     => array(
						6 => array(
							'product_id' => 42,
							'quantity'   => 1,
						),
					),
					'invoice_key'    => '',
				)
			)
		);

		self::assertSame( $legacyHash, $order->savedHash );
	}

	public function relevantOrderChanges(): array {
		return array(
			'status'           => array( array( 'status' => 'completed' ) ),
			'billing address'  => array( array( 'billing' => array( 'city' => 'Rio de Janeiro' ) ) ),
			'shipping address' => array( array( 'shipping' => array( 'postcode' => '20000000' ) ) ),
			'invoice key'      => array( array( 'invoice_key' => '35240612345678000190550010000001231234567890' ) ),
			'invoice xml'      => array( array( 'invoice_xml' => '<nfeProc><NFe/></nfeProc>' ) ),
			'shipping total'   => array( array( 'shipping_total' => '30.00' ) ),
			'item quantity'    => array( array( 'quantity' => 3 ) ),
		);
	}

	public function test_should_deliver_unchanged_order_while_force_import_is_active(): void {
		$order = $this->makeOrder();
		$this->givenOrder( $order );
		$this->deliver( 200, self::ME_WEBHOOK_ID );
		$order->storedHash = $order->savedHash;

		$order->forceImportExpiresAt = (string) ( time() + 600 );

		self::assertTrue( $this->shouldDeliver( true, self::ME_WEBHOOK_ID ) );
	}

	public function test_should_not_deliver_unchanged_order_when_force_import_expired(): void {
		$order = $this->makeOrder();
		$this->givenOrder( $order );
		$this->deliver( 200, self::ME_WEBHOOK_ID );
		$order->storedHash = $order->savedHash;

		$order->forceImportExpiresAt = (string) ( time() - 1 );

		self::assertFalse( $this->shouldDeliver( true, self::ME_WEBHOOK_ID ) );
	}

	public function test_should_deliver_keeps_false_even_when_force_import_is_active(): void {
		$order                       = $this->makeOrder();
		$order->forceImportExpiresAt = (string) ( time() + 600 );
		$this->givenOrder( $order );

		self::assertFalse( $this->shouldDeliver( false, self::ME_WEBHOOK_ID ) );
	}

	public function test_should_deliver_ignores_force_import_on_third_party_webhooks(): void {
		Functions\expect( 'wc_get_order' )->never();

		self::assertFalse( $this->shouldDeliver( false, self::OTHER_WEBHOOK_ID ) );
		self::assertTrue( $this->shouldDeliver( true, self::OTHER_WEBHOOK_ID ) );
	}

	/**
	 * @dataProvider forceImportTopics
	 */
	public function test_delivery_clears_force_import_mark_on_success( string $topic, bool $storesHash ): void {
		WC_Webhook::$records[ self::ME_WEBHOOK_ID ]['topic'] = $topic;
		$order                       = $this->makeOrder();
		$order->forceImportExpiresAt = (string) ( time() + 600 );
		$this->givenOrder( $order );

		$this->deliver( 200, self::ME_WEBHOOK_ID );

		self::assertSame( array( ForceImportService::META_KEY ), $order->deletedMeta );
		self::assertSame( $storesHash, $order->savedHash !== null );
	}

	public function forceImportTopics(): array {
		return array(
			'order.updated' => array( 'order.updated', true ),
			'order.created' => array( 'order.created', false ),
		);
	}

	public function test_delivery_keeps_force_import_mark_on_failure(): void {
		$order                       = $this->makeOrder();
		$order->forceImportExpiresAt = (string) ( time() + 600 );
		$this->givenOrder( $order );

		$this->deliver( 500, self::ME_WEBHOOK_ID );

		self::assertSame( array(), $order->deletedMeta );
	}

	public function test_delivery_keeps_force_import_mark_for_third_party_webhooks(): void {
		$order                       = $this->makeOrder();
		$order->forceImportExpiresAt = (string) ( time() + 600 );
		$this->givenOrder( $order );

		$this->deliver( 200, self::OTHER_WEBHOOK_ID );

		self::assertSame( array(), $order->deletedMeta );
	}

	public function test_delivery_stores_order_hash_on_success(): void {
		$order = $this->makeOrder();
		$this->givenOrder( $order );

		$this->deliver( 201, self::ME_WEBHOOK_ID );

		self::assertMatchesRegularExpression( '/^[a-f0-9]{32}$/', (string) $order->savedHash );
		self::assertSame( 1, $order->metaSaves );
	}

	/**
	 * @dataProvider unsuccessfulResponseCodes
	 */
	public function test_delivery_skips_unsuccessful_responses( int $code ): void {
		Functions\expect( 'wc_get_order' )->never();

		$this->deliver( $code, self::ME_WEBHOOK_ID );

		$this->assertMockExpectationsMet();
	}

	public function unsuccessfulResponseCodes(): array {
		return array(
			'no response'  => array( 0 ),
			'199'          => array( 199 ),
			'redirect'     => array( 300 ),
			'client error' => array( 404 ),
			'server error' => array( 500 ),
		);
	}

	public function test_delivery_skips_third_party_webhooks(): void {
		Functions\expect( 'wc_get_order' )->never();

		$this->deliver( 200, self::OTHER_WEBHOOK_ID );

		$this->assertMockExpectationsMet();
	}

	public function test_delivery_skips_other_topics(): void {
		WC_Webhook::$records[ self::ME_WEBHOOK_ID ]['topic'] = 'order.deleted';
		Functions\expect( 'wc_get_order' )->never();

		$this->deliver( 200, self::ME_WEBHOOK_ID );

		$this->assertMockExpectationsMet();
	}

	public function test_deliver_async_is_disabled_for_marked_order_on_melhor_integrador_order_updated(): void {
		Functions\expect( 'WC' )->never();
		$this->markOrderForImmediateDelivery();

		self::assertFalse( $this->deliverAsync( self::ME_WEBHOOK_ID ) );
	}

	public function test_delivery_cancels_pending_async_delivery_after_immediate_success(): void {
		$this->markOrderForImmediateDelivery();
		Functions\when( 'wc_get_order' )->justReturn( null );
		$queue = Mockery::mock( 'WC_Queue_Interface' );
		$queue->expects( 'cancel' )
			->once()
			->with(
				'woocommerce_deliver_webhook_async',
				array(
					'webhook_id' => self::ME_WEBHOOK_ID,
					'arg'        => self::ORDER_ID,
				),
				'woocommerce-webhooks'
			);
		$this->givenQueue( $queue );

		$this->deliver( 200, self::ME_WEBHOOK_ID );

		$this->assertMockExpectationsMet();
	}

	public function test_delivery_skips_cancel_when_queue_is_unavailable(): void {
		$this->markOrderForImmediateDelivery();
		Functions\expect( 'wc_get_order' )->once()->with( self::ORDER_ID )->andReturn( null );
		$this->givenQueue( null );

		$this->deliver( 200, self::ME_WEBHOOK_ID );

		$this->assertMockExpectationsMet();
	}

	public function test_delivery_keeps_pending_async_delivery_on_wp_error(): void {
		$this->markOrderForImmediateDelivery();
		Functions\expect( 'WC' )->never();
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( '' );

		$this->invoke( 'woocommerce_webhook_delivery', array(), new \WP_Error(), 0.5, self::ORDER_ID, self::ME_WEBHOOK_ID );

		$this->assertMockExpectationsMet();
	}

	public function test_delivery_keeps_pending_async_delivery_on_server_error(): void {
		$this->markOrderForImmediateDelivery();
		Functions\expect( 'WC' )->never();

		$this->deliver( 500, self::ME_WEBHOOK_ID );

		$this->assertMockExpectationsMet();
	}

	public function test_delivery_keeps_pending_async_delivery_when_order_is_not_marked(): void {
		Functions\expect( 'WC' )->never();
		Functions\when( 'wc_get_order' )->justReturn( null );

		$this->deliver( 200, self::ME_WEBHOOK_ID );

		$this->assertMockExpectationsMet();
	}

	public function test_delivery_keeps_pending_async_delivery_for_other_topics(): void {
		WC_Webhook::$records[ self::ME_WEBHOOK_ID ]['topic'] = 'order.created';
		$this->markOrderForImmediateDelivery();
		Functions\expect( 'WC' )->never();
		Functions\when( 'wc_get_order' )->justReturn( null );

		$this->deliver( 200, self::ME_WEBHOOK_ID );

		$this->assertMockExpectationsMet();
	}

	public function test_delivery_keeps_pending_async_delivery_for_third_party_webhooks(): void {
		$this->markOrderForImmediateDelivery();
		Functions\expect( 'WC' )->never();

		$this->deliver( 200, self::OTHER_WEBHOOK_ID );

		$this->assertMockExpectationsMet();
	}

	public function test_deliver_async_keeps_value_when_order_is_not_marked(): void {
		Functions\expect( 'WC' )->never();

		self::assertTrue( $this->deliverAsync( self::ME_WEBHOOK_ID ) );
	}

	public function test_deliver_async_keeps_value_for_other_marked_orders(): void {
		Functions\expect( 'WC' )->never();
		$this->container->get( ImmediateWebhookDeliveryService::class )->markOrder( self::ORDER_ID + 1 );

		self::assertTrue( $this->deliverAsync( self::ME_WEBHOOK_ID ) );
	}

	public function test_deliver_async_keeps_value_for_other_topics(): void {
		Functions\expect( 'WC' )->never();
		WC_Webhook::$records[ self::ME_WEBHOOK_ID ]['topic'] = 'order.created';
		$this->markOrderForImmediateDelivery();

		self::assertTrue( $this->deliverAsync( self::ME_WEBHOOK_ID ) );
	}

	public function test_deliver_async_keeps_value_for_third_party_webhooks(): void {
		Functions\expect( 'WC' )->never();
		$this->markOrderForImmediateDelivery();

		self::assertTrue( $this->deliverAsync( self::OTHER_WEBHOOK_ID ) );
	}

	public function test_deliver_async_keeps_value_received_from_other_filters(): void {
		self::assertSame(
			'custom',
			$this->invoke( 'woocommerce_webhook_deliver_async', 'custom', new WC_Webhook( self::ME_WEBHOOK_ID ), self::ORDER_ID )
		);
	}

	public function test_deliver_async_ignores_non_numeric_args(): void {
		$this->markOrderForImmediateDelivery();

		self::assertTrue(
			$this->invoke( 'woocommerce_webhook_deliver_async', true, new WC_Webhook( self::ME_WEBHOOK_ID ), array( 'action' => 'x' ) )
		);
	}

	public function test_http_args_limit_timeout_for_marked_order_on_melhor_integrador_order_updated(): void {
		$this->markOrderForImmediateDelivery();

		self::assertSame(
			array(
				'method'  => 'POST',
				'timeout' => 10.0,
			),
			$this->httpArgs( 60, self::ORDER_ID, self::ME_WEBHOOK_ID )
		);
	}

	public function test_http_args_keep_lower_timeout_from_other_filters(): void {
		$this->markOrderForImmediateDelivery();

		self::assertSame( 5.0, $this->httpArgs( 5, self::ORDER_ID, self::ME_WEBHOOK_ID )['timeout'] );
	}

	public function test_http_args_keep_timeout_when_order_is_not_marked(): void {
		self::assertSame( 60, $this->httpArgs( 60, self::ORDER_ID, self::ME_WEBHOOK_ID )['timeout'] );
	}

	public function test_http_args_keep_timeout_for_other_topics(): void {
		WC_Webhook::$records[ self::ME_WEBHOOK_ID ]['topic'] = 'order.created';
		$this->markOrderForImmediateDelivery();

		self::assertSame( 60, $this->httpArgs( 60, self::ORDER_ID, self::ME_WEBHOOK_ID )['timeout'] );
	}

	public function test_http_args_keep_timeout_for_third_party_webhooks(): void {
		$this->markOrderForImmediateDelivery();

		self::assertSame( 60, $this->httpArgs( 60, self::ORDER_ID, self::OTHER_WEBHOOK_ID )['timeout'] );
	}

	public function test_http_args_keep_timeout_for_ping(): void {
		$this->markOrderForImmediateDelivery();

		self::assertSame( 60, $this->httpArgs( 60, null, self::ME_WEBHOOK_ID )['timeout'] );
	}

	public function test_shutdown_finishes_request_when_an_order_is_marked(): void {
		$this->markOrderForImmediateDelivery();
		Functions\expect( 'fastcgi_finish_request' )->once()->andReturn( true );
		Functions\expect( 'litespeed_finish_request' )->never();

		$this->invoke( 'shutdown' );

		$this->assertMockExpectationsMet();
	}

	public function test_shutdown_keeps_request_open_when_no_order_is_marked(): void {
		Functions\expect( 'fastcgi_finish_request' )->never();
		Functions\expect( 'litespeed_finish_request' )->never();

		$this->invoke( 'shutdown' );

		$this->assertMockExpectationsMet();
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_shutdown_falls_back_to_litespeed_finish_request(): void {
		$this->markOrderForImmediateDelivery();
		Functions\expect( 'litespeed_finish_request' )->once()->andReturn( true );

		$this->invoke( 'shutdown' );

		self::assertFalse( function_exists( 'fastcgi_finish_request' ) );
		$this->assertMockExpectationsMet();
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_shutdown_ignores_missing_finish_request_functions(): void {
		$this->markOrderForImmediateDelivery();

		$this->invoke( 'shutdown' );

		self::assertFalse( function_exists( 'fastcgi_finish_request' ) );
		self::assertFalse( function_exists( 'litespeed_finish_request' ) );
	}

	public function test_delivery_skips_missing_order(): void {
		Functions\expect( 'wc_get_order' )->once()->with( self::ORDER_ID )->andReturn( null );

		$this->deliver( 200, self::ME_WEBHOOK_ID );

		$this->assertMockExpectationsMet();
	}

	/**
	 * @param mixed ...$args
	 * @return mixed
	 */
	private function invoke( string $hook, ...$args ) {
		return call_user_func_array( $this->captured[ $hook ]['callback'], $args );
	}

	private function shouldDeliver( bool $shouldDeliver, int $webhookId ): bool {
		return $this->invoke(
			'woocommerce_webhook_should_deliver',
			$shouldDeliver,
			new WC_Webhook( $webhookId ),
			self::ORDER_ID
		);
	}

	private function markOrderForImmediateDelivery(): void {
		$this->container->get( ImmediateWebhookDeliveryService::class )->markOrder( self::ORDER_ID );
	}

	private function deliverAsync( int $webhookId ) {
		return $this->invoke( 'woocommerce_webhook_deliver_async', true, new WC_Webhook( $webhookId ), self::ORDER_ID );
	}

	private function givenQueue( $queue ): void {
		$wc = Mockery::mock();
		$wc->allows( 'queue' )->andReturn( $queue );
		Functions\when( 'WC' )->justReturn( $wc );
	}

	private function httpArgs( int $timeout, $arg, int $webhookId ): array {
		return $this->invoke(
			'woocommerce_webhook_http_args',
			array(
				'method'  => 'POST',
				'timeout' => $timeout,
			),
			$arg,
			$webhookId
		);
	}

	private function deliver( int $responseCode, int $webhookId ): void {
		$response = array( 'response' => array( 'code' => $responseCode ) );
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( $responseCode );

		$this->invoke( 'woocommerce_webhook_delivery', array(), $response, 0.5, self::ORDER_ID, $webhookId );
	}

	private function captureInto( string $hook ): callable {
		return function ( $callback, $priority = 10, $args = 1 ) use ( $hook ): void {
			$this->captured[ $hook ] = array(
				'callback' => $callback,
				'priority' => $priority,
				'args'     => $args,
			);
		};
	}

	/**
	 * @param \Mockery\MockInterface&\WC_Order $order
	 */
	private function givenOrder( $order ): void {
		Functions\when( 'wc_get_order' )->alias(
			static function ( $id ) use ( $order ) {
				return $id === self::ORDER_ID ? $order : false;
			}
		);
	}

	/**
	 * Order mock whose getters read from the mutable $order->state array; the hash written by
	 * update_meta_data() lands in $order->savedHash, get_meta() returns $order->storedHash and
	 * save_meta_data() calls are counted in $order->metaSaves. The force import mark is read from
	 * $order->forceImportExpiresAt ('' = absent) and deleted keys are pushed into $order->deletedMeta.
	 *
	 * @return \Mockery\MockInterface&\WC_Order
	 */
	private function makeOrder() {
		$order             = Mockery::mock( 'WC_Order' );
		$order->state      = array(
			'status'         => 'processing',
			'billing'        => array( 'city' => 'São Paulo' ),
			'shipping'       => array( 'postcode' => '01001000' ),
			'invoice_key'    => '',
			'invoice_xml'    => '',
			'shipping_total' => '15.00',
			'quantity'       => 1,
		);
		$order->storedHash = '';
		$order->savedHash  = null;
		$order->metaSaves  = 0;

		$order->forceImportExpiresAt = '';
		$order->deletedMeta          = array();

		$order->allows( 'get_status' )->andReturnUsing(
			static function () use ( $order ) {
				return $order->state['status'];
			}
		);
		$order->allows( 'get_address' )->andReturnUsing(
			static function ( $type ) use ( $order ) {
				return $order->state[ $type ];
			}
		);
		$order->allows( 'get_shipping_methods' )->andReturnUsing(
			static function () use ( $order ) {
				$line = Mockery::mock( 'WC_Order_Item_Shipping' );
				$line->allows( 'get_method_id' )->andReturn( 'melhor_envio' );
				$line->allows( 'get_method_title' )->andReturn( 'PAC' );
				$line->allows( 'get_total' )->andReturn( $order->state['shipping_total'] );

				return array( 5 => $line );
			}
		);
		$order->allows( 'get_items' )->andReturnUsing(
			static function () use ( $order ) {
				$item = Mockery::mock( 'WC_Order_Item_Product' );
				$item->allows( 'get_product_id' )->andReturn( 42 );
				$item->allows( 'get_quantity' )->andReturn( $order->state['quantity'] );

				return array( 6 => $item );
			}
		);
		$order->allows( 'get_meta' )->andReturnUsing(
			static function ( $key ) use ( $order ) {
				if ( $key === '_me_invoice_key' ) {
					return $order->state['invoice_key'];
				}

				if ( $key === '_me_invoice_xml_danfe' ) {
					return $order->state['invoice_xml'];
				}

				if ( $key === ForceImportService::META_KEY ) {
					return $order->forceImportExpiresAt;
				}

				return $key === self::HASH_META ? $order->storedHash : '';
			}
		);
		$order->allows( 'meta_exists' )->andReturnUsing(
			static function ( $key ) use ( $order ) {
				return $key === ForceImportService::META_KEY && $order->forceImportExpiresAt !== '';
			}
		);
		$order->allows( 'delete_meta_data' )->andReturnUsing(
			static function ( $key ) use ( $order ): void {
				$order->deletedMeta[] = $key;
				if ( $key === ForceImportService::META_KEY ) {
					$order->forceImportExpiresAt = '';
				}
			}
		);
		$order->allows( 'update_meta_data' )->andReturnUsing(
			static function ( $key, $value ) use ( $order ): void {
				if ( $key === self::HASH_META ) {
					$order->savedHash = $value;
				}
			}
		);
		$order->allows( 'save_meta_data' )->andReturnUsing(
			static function () use ( $order ): void {
				++$order->metaSaves;
			}
		);

		return $order;
	}

	private function makeControllerSpy(): object {
		return new class() {

			/** @var int */
			public $registered = 0;

			public function register(): void {
				++$this->registered;
			}
		};
	}
}
