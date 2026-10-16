<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Http\Controllers\Order;

use Brain\Monkey\Functions;
use MelhorEnvio\Http\Controllers\Order\OrderBulkImportNoticeController;
use MelhorEnvio\Tests\TestCase;

final class OrderBulkImportNoticeControllerTest extends TestCase {

	private const LEGACY = 'edit-shop_order';
	private const HPOS   = 'woocommerce_page_wc-orders';

	private OrderBulkImportNoticeController $controller;

	private string $screenId = self::LEGACY;

	protected function setUp(): void {
		parent::setUp();

		$this->controller = new OrderBulkImportNoticeController();

		Functions\stubEscapeFunctions();
		Functions\stubTranslationFunctions();
		Functions\when( 'get_current_screen' )->alias(
			function () {
				return (object) array( 'id' => $this->screenId );
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
	}

	public function test_register_hooks_notice_and_removable_query_args(): void {
		$this->controller->register();

		self::assertSame( 10, has_action( 'admin_notices', array( $this->controller, 'renderNotice' ) ) );
		self::assertSame( 10, has_filter( 'removable_query_args', array( $this->controller, 'addRemovableQueryArgs' ) ) );
	}

	public function test_result_query_args_are_removable(): void {
		self::assertSame(
			array( 'updated', 'me_mi_import_sent', 'me_mi_import_skipped', 'me_mi_import_error' ),
			$this->controller->addRemovableQueryArgs( array( 'updated' ) )
		);
	}

	/**
	 * @dataProvider orderScreens
	 */
	public function test_notice_reports_sent_orders( string $screen ): void {
		$this->screenId            = $screen;
		$_GET['me_mi_import_sent'] = '3';

		$this->expectOutputString(
			'<div class="notice notice-success is-dismissible"><p>3 pedido(s) enviado(s) para sincronização na Melhor Envio. O resultado aparece nas notas de cada pedido.</p></div>'
		);

		$this->controller->renderNotice();
	}

	/**
	 * @dataProvider errorNotices
	 */
	public function test_notice_reports_errors( string $code, string $message ): void {
		$_GET['me_mi_import_error'] = $code;

		$this->expectOutputString( '<div class="notice notice-error is-dismissible"><p>' . $message . '</p></div>' );

		$this->controller->renderNotice();
	}

	public function errorNotices(): array {
		return array(
			'no_webhook' => array( 'no_webhook', 'O webhook da Melhor Envio não está configurado ou está inativo nesta loja. Reconecte a integração na Melhor Envio.' ),
			'limit'      => array( 'limit', 'Selecione no máximo 50 pedidos por vez.' ),
			'throttled'  => array( 'throttled', 'Aguarde alguns segundos antes de enviar uma nova sincronização.' ),
			'empty'      => array( 'empty', 'Selecione ao menos um pedido.' ),
		);
	}

	public function test_notice_ignores_unknown_error_code(): void {
		$_GET['me_mi_import_error'] = '<script>';

		$this->expectOutputString( '' );

		$this->controller->renderNotice();
	}

	public function test_notice_is_not_rendered_outside_order_screens(): void {
		$this->screenId            = 'dashboard';
		$_GET['me_mi_import_sent'] = '3';

		$this->expectOutputString( '' );

		$this->controller->renderNotice();
	}

	public function test_notice_is_not_rendered_before_screen_is_set(): void {
		Functions\when( 'get_current_screen' )->justReturn( null );
		$_GET['me_mi_import_sent'] = '3';

		$this->expectOutputString( '' );

		$this->controller->renderNotice();
	}

	/**
	 * @dataProvider orderScreens
	 */
	public function test_notice_is_not_rendered_without_result_query_args( string $screen ): void {
		$this->screenId = $screen;

		$this->expectOutputString( '' );

		$this->controller->renderNotice();
	}

	public function test_notice_reports_skipped_orders(): void {
		$_GET['me_mi_import_sent']    = '2';
		$_GET['me_mi_import_skipped'] = '3';

		$this->expectOutputString(
			'<div class="notice notice-success is-dismissible"><p>2 pedido(s) enviado(s) para sincronização na Melhor Envio. O resultado aparece nas notas de cada pedido. 3 pedido(s) concluído(s) ou cancelado(s) foram ignorados.</p></div>'
		);

		$this->controller->renderNotice();
	}

	public function orderScreens(): array {
		return array(
			'legacy' => array( self::LEGACY ),
			'hpos'   => array( self::HPOS ),
		);
	}
}
