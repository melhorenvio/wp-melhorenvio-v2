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
use MelhorEnvio\Http\Controllers\Order\OrderInvoiceKeyMetaBoxController;
use MelhorEnvio\Http\Controllers\Order\OrderMetaBackfillController;
use MelhorEnvio\Http\Controllers\Order\OrderNoteInvoiceKeyController;
use MelhorEnvio\Http\Controllers\Quotation\QuotationController;
use MelhorEnvio\Http\Controllers\Settings\GetSettingsController;
use MelhorEnvio\Http\Controllers\Settings\SaveSettingsController;
use MelhorEnvio\Services\Auth\SignatureService;
use MelhorEnvio\Services\Shipping\MelhorEnvioShippingService;
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
	);

	private const CAPTURED_FILTERS = array(
		'woocommerce_hidden_order_itemmeta',
		'woocommerce_shipping_methods',
		'woocommerce_webhook_should_deliver',
	);

	private const CAPTURED_ACTIONS = array(
		'woocommerce_auth_page_footer',
		'woocommerce_webhook_delivery',
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

	public function test_register_hooks_logout_and_ssl_filters(): void {
		self::assertSame( 10, has_action( 'wp_logout', array( $this->manager, 'onUserLogout' ) ) );
		self::assertSame( 10, has_filter( 'https_ssl_verify', '__return_false' ) );
		self::assertSame( 10, has_filter( 'https_local_ssl_verify', '__return_false' ) );
	}

	public function test_webhook_hooks_accept_all_arguments(): void {
		self::assertSame( 10, $this->captured['woocommerce_webhook_should_deliver']['priority'] );
		self::assertSame( 3, $this->captured['woocommerce_webhook_should_deliver']['args'] );
		self::assertSame( 10, $this->captured['woocommerce_webhook_delivery']['priority'] );
		self::assertSame( 5, $this->captured['woocommerce_webhook_delivery']['args'] );
	}

	public function test_on_user_logout_deletes_signature(): void {
		$this->container->singleton( SignatureService::class, SignatureService::class );
		Functions\expect( 'delete_transient' )->once()->with( 'melhor_envio_integrador_signature' )->andReturn( true );

		$this->manager->onUserLogout();
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

	public function relevantOrderChanges(): array {
		return array(
			'status'           => array( array( 'status' => 'completed' ) ),
			'billing address'  => array( array( 'billing' => array( 'city' => 'Rio de Janeiro' ) ) ),
			'shipping address' => array( array( 'shipping' => array( 'postcode' => '20000000' ) ) ),
			'invoice key'      => array( array( 'invoice_key' => '35240612345678000190550010000001231234567890' ) ),
			'shipping total'   => array( array( 'shipping_total' => '30.00' ) ),
			'item quantity'    => array( array( 'quantity' => 3 ) ),
		);
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
	}

	public function test_delivery_skips_other_topics(): void {
		WC_Webhook::$records[ self::ME_WEBHOOK_ID ]['topic'] = 'order.deleted';
		Functions\expect( 'wc_get_order' )->never();

		$this->deliver( 200, self::ME_WEBHOOK_ID );
	}

	public function test_delivery_skips_missing_order(): void {
		Functions\expect( 'wc_get_order' )->once()->with( self::ORDER_ID )->andReturn( null );

		$this->deliver( 200, self::ME_WEBHOOK_ID );
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
	 * save_meta_data() calls are counted in $order->metaSaves.
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
			'shipping_total' => '15.00',
			'quantity'       => 1,
		);
		$order->storedHash = '';
		$order->savedHash  = null;
		$order->metaSaves  = 0;

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

				return $key === self::HASH_META ? $order->storedHash : '';
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
