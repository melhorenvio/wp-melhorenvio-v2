<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Http\Controllers\Order;

use Brain\Monkey\Functions;
use MelhorEnvio\Http\Controllers\Order\OrderBulkImportController;
use MelhorEnvio\Services\Auth\SecretService;
use MelhorEnvio\Services\Order\ForceImportService;
use MelhorEnvio\Services\Webhook\MelhorIntegradorWebhookService;
use MelhorEnvio\Tests\TestCase;
use Mockery;
use RuntimeException;
use WC_Data_Store;
use WC_Webhook;

final class OrderBulkImportControllerTest extends TestCase {

	private const ACTION       = 'melhor_integrador_import';
	private const LEGACY       = 'edit-shop_order';
	private const HPOS         = 'woocommerce_page_wc-orders';
	private const USER_ID      = 7;
	private const LOCK_KEY     = 'melhor_integrador_import_lock_7';
	private const REDIRECT     = 'https://shop.test/wp-admin/edit.php?post_type=shop_order&paged=2';
	private const MI_URL       = 'https://webhook-wordpress-envios.melhorenvio.com.br/hook';
	private const UPDATED_HOOK = 11;
	private const CREATED_HOOK = 12;

	private OrderBulkImportController $controller;

	/** @var array<string, mixed> */
	private array $options = array();

	private string $screenId = self::LEGACY;

	/** @var array<int, \Mockery\MockInterface&\WC_Order> */
	private array $orders = array();

	private bool $canManage = true;

	/** @var mixed */
	private $lock = false;

	/** @var string[] Actions passed to check_admin_referer(). */
	private array $nonceChecks = array();

	/** @var array<int, array> Arguments of each set_transient() call. */
	private array $transientsSet = array();

	/** @var int[] Ids passed to wc_get_order(). */
	private array $orderLookups = array();

	protected function setUp(): void {
		parent::setUp();

		$this->controller = new OrderBulkImportController(
			new SecretService(),
			new MelhorIntegradorWebhookService(),
			new ForceImportService()
		);

		$this->options = array(
			'melhor_envio_ui_mode'           => 'integrador',
			'melhor_envio_integrador_secret' => 'base64:abc',
		);

		$this->givenWebhooks(
			array(
				self::UPDATED_HOOK => array( 'delivery_url' => self::MI_URL, 'status' => 'active', 'topic' => 'order.updated' ),
				self::CREATED_HOOK => array( 'delivery_url' => self::MI_URL, 'status' => 'active', 'topic' => 'order.created' ),
				13                 => array( 'delivery_url' => 'https://example.com/hook', 'status' => 'active', 'topic' => 'order.updated' ),
			)
		);

		Functions\stubEscapeFunctions();
		Functions\stubTranslationFunctions();
		Functions\when( 'get_option' )->alias(
			function ( $key, $default = false ) {
				return $this->options[ $key ] ?? $default;
			}
		);
		Functions\when( 'get_current_screen' )->alias(
			function () {
				return (object) array( 'id' => $this->screenId );
			}
		);
		Functions\when( 'get_current_user_id' )->justReturn( self::USER_ID );
		Functions\when( 'current_user_can' )->alias(
			function ( $capability ) {
				return $capability === 'manage_woocommerce' && $this->canManage;
			}
		);
		Functions\when( 'check_admin_referer' )->alias(
			function ( $action ) {
				$this->nonceChecks[] = $action;
				return 1;
			}
		);
		Functions\when( 'get_transient' )->alias(
			function ( $key ) {
				return $key === self::LOCK_KEY ? $this->lock : false;
			}
		);
		Functions\when( 'set_transient' )->alias(
			function ( ...$args ) {
				$this->transientsSet[] = $args;
				return true;
			}
		);
		Functions\when( 'absint' )->alias(
			static function ( $value ): int {
				return abs( (int) $value );
			}
		);
		Functions\when( 'wp_unslash' )->returnArg();
		Functions\when( 'sanitize_key' )->alias(
			static function ( $key ): string {
				return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
			}
		);
		Functions\when( 'wc_get_order' )->alias(
			function ( $id ) {
				$this->orderLookups[] = $id;
				return $this->orders[ $id ] ?? false;
			}
		);
		Functions\when( 'wp_die' )->alias(
			static function ( $message = '' ) {
				throw new RuntimeException( 'wp_die: ' . $message );
			}
		);
		Functions\when( 'remove_query_arg' )->alias(
			static function ( $keys, $url ) {
				$parts = explode( '?', $url, 2 );
				parse_str( $parts[1] ?? '', $query );
				$query = array_diff_key( $query, array_flip( (array) $keys ) );

				return $parts[0] . ( $query ? '?' . http_build_query( $query ) : '' );
			}
		);
		Functions\when( 'add_query_arg' )->alias(
			static function ( $key, $value, $url ) {
				return $url . ( str_contains( $url, '?' ) ? '&' : '?' ) . $key . '=' . $value;
			}
		);
	}

	public function test_register_hooks_both_order_screens_column_and_footer(): void {
		$this->controller->register();

		foreach ( array( self::LEGACY, self::HPOS ) as $screen ) {
			self::assertSame( 10, has_filter( 'bulk_actions-' . $screen, array( $this->controller, 'addBulkAction' ) ) );
			self::assertSame( 10, has_filter( 'handle_bulk_actions-' . $screen, array( $this->controller, 'handleBulkAction' ) ) );
		}
		self::assertSame( 10, has_filter( 'manage_edit-shop_order_columns', array( $this->controller, 'addColumn' ) ) );
		self::assertSame( 10, has_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this->controller, 'addColumn' ) ) );
		self::assertSame( 10, has_action( 'manage_shop_order_posts_custom_column', array( $this->controller, 'renderLegacyColumn' ) ) );
		self::assertSame( 10, has_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this->controller, 'renderHposColumn' ) ) );
		self::assertSame( 10, has_action( 'admin_post_melhor_integrador_import_order', array( $this->controller, 'handleSingleImport' ) ) );
		self::assertSame( 10, has_action( 'admin_head', array( $this->controller, 'renderColumnStyle' ) ) );
		self::assertSame( 10, has_action( 'admin_footer', array( $this->controller, 'renderSubmitGuard' ) ) );
	}

	public function test_bulk_action_is_added_when_integration_is_ready(): void {
		self::assertSame(
			array(
				'trash'      => 'Mover para lixeira',
				self::ACTION => 'Sincronizar na Melhor Envio',
			),
			$this->controller->addBulkAction( array( 'trash' => 'Mover para lixeira' ) )
		);
	}

	public function test_bulk_action_is_added_even_if_webhook_is_inactive(): void {
		$this->givenWebhooks( array( 1 => array( 'delivery_url' => self::MI_URL, 'status' => 'disabled', 'topic' => 'order.updated' ) ) );

		self::assertArrayHasKey( self::ACTION, $this->controller->addBulkAction( array() ) );
	}

	public function test_bulk_action_is_hidden_in_legacy_mode(): void {
		$this->options['melhor_envio_ui_mode'] = 'legacy';

		self::assertSame( array(), $this->controller->addBulkAction( array() ) );
	}

	public function test_bulk_action_is_hidden_without_secret(): void {
		unset( $this->options['melhor_envio_integrador_secret'] );

		self::assertSame( array(), $this->controller->addBulkAction( array() ) );
	}

	public function test_bulk_action_is_hidden_without_melhor_integrador_webhook(): void {
		$this->givenWebhooks( array( 1 => array( 'delivery_url' => 'https://example.com/hook', 'status' => 'active', 'topic' => 'order.updated' ) ) );

		self::assertSame( array(), $this->controller->addBulkAction( array() ) );
	}

	public function test_other_bulk_actions_pass_through_untouched(): void {
		self::assertSame( self::REDIRECT, $this->controller->handleBulkAction( self::REDIRECT, 'mark_completed', array( 1 ) ) );
		self::assertSame( array(), $this->nonceChecks );
		self::assertSame( array(), $this->orderLookups );
	}

	/**
	 * @dataProvider screenNonces
	 */
	public function test_handler_checks_list_table_nonce( string $screen, string $nonceAction ): void {
		$this->screenId = $screen;

		$this->controller->handleBulkAction( self::REDIRECT, self::ACTION, array() );

		self::assertSame( array( $nonceAction ), $this->nonceChecks );
	}

	public function screenNonces(): array {
		return array(
			'legacy posts screen' => array( self::LEGACY, 'bulk-posts' ),
			'hpos orders screen'  => array( self::HPOS, 'bulk-orders' ),
		);
	}

	public function test_handler_dies_without_capability(): void {
		$this->canManage = false;

		try {
			$this->controller->handleBulkAction( self::REDIRECT, self::ACTION, array( 1 ) );
			self::fail( 'wp_die was not called.' );
		} catch ( RuntimeException $e ) {
			self::assertSame( 'wp_die: Você não tem permissão para sincronizar pedidos.', $e->getMessage() );
		}

		self::assertSame( array(), $this->transientsSet );
		self::assertSame( array(), $this->orderLookups );
	}

	public function test_handler_is_throttled_per_user(): void {
		$this->givenOrder( 1 );
		$this->lock = 1;

		$result = $this->controller->handleBulkAction( self::REDIRECT, self::ACTION, array( 1 ) );

		self::assertSame( self::REDIRECT . '&me_mi_import_error=throttled', $result );
		self::assertSame( array(), $this->transientsSet );
		self::assertSame( array(), WC_Webhook::$processed );
	}

	public function test_handler_rejects_more_than_max_orders(): void {
		$result = $this->controller->handleBulkAction( self::REDIRECT, self::ACTION, range( 1, 51 ) );

		self::assertSame( self::REDIRECT . '&me_mi_import_error=limit', $result );
		self::assertSame( array(), $this->transientsSet );
		self::assertSame( array(), $this->orderLookups );
	}

	public function test_handler_rejects_empty_selection(): void {
		$result = $this->controller->handleBulkAction( self::REDIRECT, self::ACTION, array() );

		self::assertSame( self::REDIRECT . '&me_mi_import_error=empty', $result );
	}

	public function test_handler_reports_missing_active_webhook(): void {
		$this->givenWebhooks( array( 1 => array( 'delivery_url' => self::MI_URL, 'status' => 'disabled', 'topic' => 'order.updated' ) ) );
		$order = $this->givenOrder( 1 );
		$order->expects( 'update_meta_data' )->never();

		$result = $this->controller->handleBulkAction( self::REDIRECT, self::ACTION, array( 1 ) );

		self::assertSame( self::REDIRECT . '&me_mi_import_error=no_webhook', $result );
		self::assertSame( array(), $this->transientsSet );
	}

	public function test_handler_marks_orders_and_asks_woocommerce_to_deliver(): void {
		$first  = $this->givenOrder( 101 );
		$second = $this->givenOrder( 102 );
		foreach ( array( $first, $second ) as $order ) {
			$order->expects( 'update_meta_data' )->with( ForceImportService::META_KEY, Mockery::type( 'int' ) );
			$order->expects( 'save_meta_data' );
			$order->expects( 'add_order_note' )->with( 'Sincronização na Melhor Envio solicitada.' );
		}
		$result = $this->controller->handleBulkAction(
			self::REDIRECT . '&me_mi_import_error=limit&me_mi_import_sent=9',
			self::ACTION,
			array( '101', 999, 102 )
		);

		self::assertSame( self::REDIRECT . '&me_mi_import_sent=2', $result );
		self::assertSame( array( array( self::LOCK_KEY, 1, 30 ) ), $this->transientsSet );
		self::assertSame( array( array( self::UPDATED_HOOK, 101 ), array( self::UPDATED_HOOK, 102 ) ), WC_Webhook::$processed );
	}

	public function test_handler_falls_back_to_order_created_webhook(): void {
		unset( WC_Webhook::$records[ self::UPDATED_HOOK ] );
		$this->givenWebhooks( WC_Webhook::$records );
		$order = $this->givenOrder( 101 );
		$order->allows( 'update_meta_data' );
		$order->allows( 'save_meta_data' );
		$order->allows( 'add_order_note' );

		$result = $this->controller->handleBulkAction( self::REDIRECT, self::ACTION, array( 101 ) );

		self::assertSame( self::REDIRECT . '&me_mi_import_sent=1', $result );
		self::assertSame( array( array( self::CREATED_HOOK, 101 ) ), WC_Webhook::$processed );
	}

	public function orderScreens(): array {
		return array(
			'legacy' => array( self::LEGACY ),
			'hpos'   => array( self::HPOS ),
		);
	}

	public function test_submit_guard_disables_apply_buttons_for_our_action(): void {
		$this->expectOutputRegex(
			"/<script>.*var action = 'melhor_integrador_import';.*'Enviando…'.*bulk-action-selector-top.*bulk-action-selector-bottom.*'doaction', 'doaction2'.*disabled = true.*<\/script>/s"
		);

		$this->controller->renderSubmitGuard();
	}

	public function test_submit_guard_is_not_rendered_outside_order_screens(): void {
		$this->screenId = 'dashboard';

		$this->expectOutputString( '' );

		$this->controller->renderSubmitGuard();
	}

	public function test_submit_guard_is_not_rendered_when_action_is_hidden(): void {
		$this->options['melhor_envio_ui_mode'] = 'legacy';

		$this->expectOutputString( '' );

		$this->controller->renderSubmitGuard();
	}

	/**
	 * @dataProvider orderScreens
	 */
	public function test_column_style_is_rendered_on_order_screens( string $screen ): void {
		$this->screenId = $screen;

		ob_start();
		$this->controller->renderColumnStyle();
		$output = (string) ob_get_clean();

		self::assertStringContainsString( '<style>', $output );
		self::assertStringContainsString( '.column-melhor_envio_import { width: 7em; text-align: center !important; }', $output );
		self::assertStringContainsString( '.me-import-order-button.me-import-sending { pointer-events: none; }', $output );
	}

	public function test_column_style_is_not_rendered_outside_order_screens(): void {
		$this->screenId = 'dashboard';

		$this->expectOutputString( '' );

		$this->controller->renderColumnStyle();
	}

	public function test_handler_reports_when_no_selected_order_exists(): void {
		$result = $this->controller->handleBulkAction( self::REDIRECT, self::ACTION, array( 999 ) );

		self::assertSame( self::REDIRECT . '&me_mi_import_error=not_found', $result );
		self::assertSame( array(), WC_Webhook::$processed );
	}

	/**
	 * @dataProvider blockedStatuses
	 */
	public function test_handler_skips_completed_and_cancelled_orders( string $status ): void {
		$allowed = $this->givenOrder( 101 );
		$allowed->expects( 'update_meta_data' )->with( ForceImportService::META_KEY, Mockery::type( 'int' ) );
		$allowed->expects( 'save_meta_data' );
		$allowed->expects( 'add_order_note' );
		$blocked = $this->givenOrder( 102, $status );
		$blocked->expects( 'update_meta_data' )->never();
		$blocked->expects( 'add_order_note' )->with( 'Sincronização na Melhor Envio não realizada: pedidos concluídos ou cancelados não podem ser sincronizados.' );

		$result = $this->controller->handleBulkAction( self::REDIRECT, self::ACTION, array( 101, 102 ) );

		self::assertSame( self::REDIRECT . '&me_mi_import_sent=1&me_mi_import_skipped=1', $result );
		self::assertSame( array( array( self::UPDATED_HOOK, 101 ) ), WC_Webhook::$processed );
	}

	/**
	 * @dataProvider blockedStatuses
	 */
	public function test_handler_reports_when_only_blocked_orders_are_selected( string $status ): void {
		$blocked = $this->givenOrder( 102, $status );
		$blocked->expects( 'update_meta_data' )->never();
		$blocked->expects( 'add_order_note' )->with( 'Sincronização na Melhor Envio não realizada: pedidos concluídos ou cancelados não podem ser sincronizados.' );

		$result = $this->controller->handleBulkAction( self::REDIRECT, self::ACTION, array( 102 ) );

		self::assertSame( self::REDIRECT . '&me_mi_import_error=blocked_status', $result );
		self::assertSame( array(), WC_Webhook::$processed );
	}

	public function blockedStatuses(): array {
		return array(
			'completed' => array( 'completed' ),
			'cancelled' => array( 'cancelled' ),
		);
	}

	/**
	 * @dataProvider blockedStatuses
	 */
	public function test_column_renders_disabled_button_for_completed_and_cancelled_orders( string $status ): void {
		$this->givenNonceUrls();
		$order = $this->givenOrder( 77, $status );
		$this->givenOrder( 55, $status );
		$disabled = '<span class="button disabled no-link me-import-order-button" aria-disabled="true" title="Pedidos concluídos ou cancelados não podem ser sincronizados na Melhor Envio." aria-label="Pedidos concluídos ou cancelados não podem ser sincronizados na Melhor Envio."><span class="dashicons dashicons-update" aria-hidden="true"></span></span>';

		$this->expectOutputString( $disabled . $disabled );

		$this->controller->renderHposColumn( OrderBulkImportController::COLUMN, $order );
		$this->controller->renderLegacyColumn( OrderBulkImportController::COLUMN, 55 );
	}

	public function test_column_is_added_when_integration_is_ready(): void {
		self::assertSame(
			array( 'order_number' => 'Pedido', OrderBulkImportController::COLUMN => 'Melhor Envio' ),
			$this->controller->addColumn( array( 'order_number' => 'Pedido' ) )
		);
	}

	public function test_column_is_hidden_in_legacy_mode(): void {
		$this->options['melhor_envio_ui_mode'] = 'legacy';

		self::assertSame( array( 'order_number' => 'Pedido' ), $this->controller->addColumn( array( 'order_number' => 'Pedido' ) ) );
	}

	public function test_legacy_column_renders_icon_button_with_tooltip(): void {
		$this->givenNonceUrls();
		$this->givenOrder( 55 );

		$this->expectOutputString( $this->expectedButton( 55 ) );

		$this->controller->renderLegacyColumn( OrderBulkImportController::COLUMN, 55 );
		$this->controller->renderLegacyColumn( 'order_status', 55 );
	}

	public function test_hpos_column_renders_icon_button_with_tooltip(): void {
		$this->givenNonceUrls();
		$order = $this->givenOrder( 77 );

		$this->expectOutputString( $this->expectedButton( 77 ) );

		$this->controller->renderHposColumn( OrderBulkImportController::COLUMN, $order );
		$this->controller->renderHposColumn( 'order_status', $order );
	}

	public function test_single_import_marks_order_and_redirects_back(): void {
		$order = $this->givenOrder( 101 );
		$order->expects( 'update_meta_data' )->with( ForceImportService::META_KEY, Mockery::type( 'int' ) );
		$order->expects( 'save_meta_data' );
		$order->expects( 'add_order_note' )->with( 'Sincronização na Melhor Envio solicitada.' );
		$_GET['order_id'] = '101';
		$this->givenSingleImportRedirect( self::REDIRECT . '&me_mi_import_sent=4' );

		$this->expectExceptionMessage( 'redirect: ' . self::REDIRECT . '&me_mi_import_sent=1' );

		try {
			$this->controller->handleSingleImport();
		} finally {
			self::assertSame( array( 'melhor_integrador_import_order_101' ), $this->nonceChecks );
			self::assertSame( array( array( self::LOCK_KEY . '_101', 1, 30 ) ), $this->transientsSet );
			self::assertSame( array( array( self::UPDATED_HOOK, 101 ) ), WC_Webhook::$processed );
		}
	}

	public function test_single_import_is_not_blocked_by_bulk_lock(): void {
		$order = $this->givenOrder( 101 );
		$order->allows( 'update_meta_data' );
		$order->allows( 'save_meta_data' );
		$order->allows( 'add_order_note' );
		$this->lock       = 1;
		$_GET['order_id'] = '101';
		$this->givenSingleImportRedirect( self::REDIRECT );

		$this->expectExceptionMessage( 'redirect: ' . self::REDIRECT . '&me_mi_import_sent=1' );

		$this->controller->handleSingleImport();
	}

	public function test_single_import_dies_without_capability(): void {
		$this->canManage  = false;
		$_GET['order_id'] = '101';
		$this->givenSingleImportRedirect( self::REDIRECT );

		$this->expectExceptionMessage( 'wp_die: Você não tem permissão para sincronizar pedidos.' );

		$this->controller->handleSingleImport();
	}

	public function test_single_import_reports_missing_active_webhook(): void {
		$this->givenWebhooks( array( 1 => array( 'delivery_url' => self::MI_URL, 'status' => 'disabled', 'topic' => 'order.updated' ) ) );
		$this->givenOrder( 101 )->expects( 'update_meta_data' )->never();
		$_GET['order_id'] = '101';
		$this->givenSingleImportRedirect( self::REDIRECT );

		$this->expectExceptionMessage( 'redirect: ' . self::REDIRECT . '&me_mi_import_error=no_webhook' );

		$this->controller->handleSingleImport();
	}

	public function test_single_import_falls_back_to_orders_list_without_referer(): void {
		$_GET['order_id'] = '999';
		$this->givenSingleImportRedirect( false );

		$this->expectExceptionMessage( 'redirect: https://shop.test/wp-admin/edit.php?post_type=shop_order&me_mi_import_error=not_found' );

		$this->controller->handleSingleImport();
	}

	private function givenNonceUrls(): void {
		Functions\when( 'admin_url' )->alias(
			static function ( $path ) {
				return 'https://shop.test/wp-admin/' . $path;
			}
		);
		Functions\when( 'wp_nonce_url' )->alias(
			static function ( $url, $action ) {
				return $url . '&_wpnonce=' . $action;
			}
		);
	}

	/**
	 * @param string|false $referer
	 */
	private function givenSingleImportRedirect( $referer ): void {
		$this->givenNonceUrls();
		Functions\when( 'wp_get_referer' )->justReturn( $referer );
		Functions\when( 'wp_safe_redirect' )->alias(
			static function ( $url ) {
				throw new RuntimeException( 'redirect: ' . $url );
			}
		);
	}

	private function expectedButton( int $orderId ): string {
		return sprintf(
			'<a class="button me-import-order-button" href="https://shop.test/wp-admin/admin-post.php?action=melhor_integrador_import_order&order_id=%1$d&_wpnonce=melhor_integrador_import_order_%1$d" title="Sincronizar na Melhor Envio" aria-label="Sincronizar na Melhor Envio"><span class="dashicons dashicons-update" aria-hidden="true"></span></a>',
			$orderId
		);
	}

	/**
	 * @param array<int, array<string, string>> $records
	 */
	private function givenWebhooks( array $records ): void {
		WC_Webhook::$records = $records;

		$store = Mockery::mock();
		$store->allows( 'get_webhooks_ids' )->andReturnUsing(
			static function ( $status = '' ) use ( $records ): array {
				return array_keys(
					array_filter(
						$records,
						static fn( array $record ): bool => $status === '' || $record['status'] === $status
					)
				);
			}
		);
		WC_Data_Store::$stores['webhook'] = $store;
	}

	/**
	 * @return \Mockery\MockInterface&\WC_Order
	 */
	private function givenOrder( int $id, string $status = 'processing' ) {
		$order = Mockery::mock( 'WC_Order' );
		$order->allows( 'get_id' )->andReturn( $id );
		$order->allows( 'has_status' )->andReturnUsing(
			static function ( $statuses ) use ( $status ): bool {
				return in_array( $status, (array) $statuses, true );
			}
		);
		$this->orders[ $id ] = $order;

		return $order;
	}
}
