<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Http\Controllers\Admin;

use Brain\Monkey\Functions;
use MelhorEnvio\Http\Controllers\Admin\ModeNoticeController;
use MelhorEnvio\Tests\TestCase;
use Mockery;
use RuntimeException;

final class ModeNoticeControllerTest extends TestCase {

	private const NONCE_KEY        = 'melhor_envio_mode_nonce';
	private const DISMISS_META_KEY = 'melhor_envio_mode_notice_dismissed';
	private const MODE_OPTION      = 'melhor_envio_ui_mode';

	private ModeNoticeController $controller;

	/** @var mixed */
	private $originalWpdb;

	protected function setUp(): void {
		parent::setUp();
		$this->controller   = new ModeNoticeController();
		$this->originalWpdb = $GLOBALS['wpdb'] ?? null;

		Functions\stubEscapeFunctions();
		Functions\stubTranslationFunctions();
		Functions\when( 'get_current_user_id' )->justReturn( 7 );
		Functions\when( 'admin_url' )->alias(
			function ( $path = '' ) {
				return 'https://shop.test/wp-admin/' . $path;
			}
		);
		Functions\when( 'wp_die' )->alias(
			function ( $message = '' ) {
				throw new RuntimeException( 'wp_die: ' . $message );
			}
		);
		Functions\when( 'wp_safe_redirect' )->alias(
			function ( $location ) {
				throw new RuntimeException( 'redirect: ' . $location );
			}
		);
	}

	protected function tearDown(): void {
		$_REQUEST        = array();
		$GLOBALS['wpdb'] = $this->originalWpdb;
		parent::tearDown();
	}

	public function test_register_hooks_notice_and_admin_post_action(): void {
		$this->controller->register();

		self::assertNotFalse( has_action( 'admin_notices', array( $this->controller, 'renderNotice' ) ) );
		self::assertNotFalse( has_action( 'admin_post_melhor_envio_set_mode', array( $this->controller, 'handleModeSwitch' ) ) );
	}

	public function test_render_notice_shows_upgrade_notice_in_legacy_mode(): void {
		$this->givenMode( 'legacy' );
		Functions\expect( 'get_user_meta' )->once()->with( 7, self::DISMISS_META_KEY, true )->andReturn( '' );
		Functions\expect( 'wp_nonce_field' )
			->times( 3 )
			->with( self::NONCE_KEY, '_wpnonce', false )
			->andReturnUsing(
				function () {
					echo '<input name="_wpnonce" value="nonce">';
				}
			);

		$output = $this->captureOutput(
			function () {
				$this->controller->renderNotice();
			}
		);

		self::assertStringContainsString( '<style>', $output );
		self::assertStringContainsString( 'class="notice me-alert"', $output );
		self::assertStringContainsString( 'action="https://shop.test/wp-admin/admin-post.php"', $output );
		self::assertStringContainsString( 'name="action" value="melhor_envio_set_mode"', $output );
		self::assertStringContainsString( 'name="mode" value="integrador"', $output );
		self::assertSame( 2, substr_count( $output, 'name="mode" value="dismiss"' ) );
		self::assertStringContainsString( 'Gestão centralizada', $output );
		self::assertStringContainsString( 'Etiquetas em lote', $output );
		self::assertStringContainsString( 'Regras de frete inteligentes', $output );
		self::assertStringContainsString( 'Migrar para nova versão', $output );
	}

	public function test_render_notice_outputs_nothing_in_integrador_mode(): void {
		$this->givenMode( 'integrador' );
		Functions\expect( 'get_user_meta' )->never();

		$output = $this->captureOutput(
			function () {
				$this->controller->renderNotice();
			}
		);

		self::assertSame( '', $output );
	}

	public function test_render_notice_outputs_nothing_when_dismissed_by_user(): void {
		$this->givenMode( 'legacy' );
		Functions\expect( 'get_user_meta' )->once()->with( 7, self::DISMISS_META_KEY, true )->andReturn( '1' );

		$output = $this->captureOutput(
			function () {
				$this->controller->renderNotice();
			}
		);

		self::assertSame( '', $output );
	}

	public function test_handle_mode_switch_dies_when_nonce_is_invalid(): void {
		$_REQUEST['mode'] = 'integrador';
		Functions\expect( 'check_admin_referer' )->once()->with( self::NONCE_KEY )->andReturn( false );
		Functions\expect( 'update_option' )->never();
		Functions\expect( 'update_user_meta' )->never();

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'wp_die: Ação não autorizada.' );

		$this->controller->handleModeSwitch();
	}

	public function test_handle_mode_switch_dismiss_stores_user_meta_and_redirects_to_referer(): void {
		$this->givenValidNonce();
		$this->givenSanitization();
		$_REQUEST['mode'] = 'dismiss';
		Functions\when( 'wp_get_referer' )->justReturn( 'https://shop.test/wp-admin/edit.php' );
		Functions\expect( 'update_user_meta' )->once()->with( 7, self::DISMISS_META_KEY, 1 );
		Functions\expect( 'update_option' )->never();

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'redirect: https://shop.test/wp-admin/edit.php' );

		$this->controller->handleModeSwitch();
	}

	public function test_handle_mode_switch_dismiss_falls_back_to_admin_url_without_referer(): void {
		$this->givenValidNonce();
		$this->givenSanitization();
		$_REQUEST['mode'] = 'dismiss';
		Functions\when( 'wp_get_referer' )->justReturn( false );
		Functions\expect( 'update_user_meta' )->once();

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'redirect: https://shop.test/wp-admin/' );

		$this->controller->handleModeSwitch();
	}

	/**
	 * @dataProvider invalidModes
	 */
	public function test_handle_mode_switch_dies_on_invalid_mode( ?string $mode ): void {
		$this->givenValidNonce();
		$this->givenSanitization();
		if ( $mode !== null ) {
			$_REQUEST['mode'] = $mode;
		}
		Functions\expect( 'update_option' )->never();
		Functions\expect( 'wp_safe_redirect' )->never();

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'wp_die: Modo inválido.' );

		$this->controller->handleModeSwitch();
	}

	public function invalidModes(): array {
		return array(
			'missing'       => array( null ),
			'empty'         => array( '' ),
			'unknown'       => array( 'turbo' ),
			'wrong case'    => array( 'Integrador' ),
		);
	}

	/**
	 * @dataProvider validModes
	 */
	public function test_handle_mode_switch_persists_mode_and_redirects_to_its_page( string $mode, int $enabled, string $page ): void {
		$this->givenValidNonce();
		$this->givenSanitization();
		$_REQUEST['mode'] = $mode;

		$wpdb         = Mockery::mock();
		$wpdb->prefix = 'wp_';
		$wpdb->expects( 'update' )->with(
			'wp_woocommerce_shipping_zone_methods',
			array( 'is_enabled' => $enabled ),
			array( 'method_id' => 'melhor_envio' )
		);
		$GLOBALS['wpdb'] = $wpdb;

		Functions\expect( 'update_option' )->once()->with( self::MODE_OPTION, $mode );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'redirect: https://shop.test/wp-admin/admin.php?page=' . $page );

		$this->controller->handleModeSwitch();
	}

	public function validModes(): array {
		return array(
			'integrador' => array( 'integrador', 1, 'melhor-integrador' ),
			'legacy'     => array( 'legacy', 0, 'melhor-envio' ),
		);
	}

	private function givenMode( string $mode ): void {
		Functions\when( 'get_option' )->alias(
			function ( $key, $default = false ) use ( $mode ) {
				return $key === self::MODE_OPTION ? $mode : $default;
			}
		);
	}

	private function givenValidNonce(): void {
		Functions\expect( 'check_admin_referer' )->once()->with( self::NONCE_KEY )->andReturn( 1 );
	}

	private function givenSanitization(): void {
		Functions\when( 'wp_unslash' )->returnArg();
		Functions\when( 'sanitize_text_field' )->alias(
			function ( $value ) {
				return trim( strip_tags( (string) $value ) );
			}
		);
	}

	private function captureOutput( callable $callback ): string {
		ob_start();
		try {
			$callback();
		} finally {
			$output = (string) ob_get_clean();
		}

		return $output;
	}
}
