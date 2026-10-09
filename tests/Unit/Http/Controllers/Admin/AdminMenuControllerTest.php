<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Http\Controllers\Admin;

use Brain\Monkey\Functions;
use MelhorEnvio\Core\Container;
use MelhorEnvio\Http\Controllers\Admin\AdminMenuController;
use MelhorEnvio\Services\Auth\SecretService;
use MelhorEnvio\Services\Auth\SignatureService;
use MelhorEnvio\Tests\TestCase;

final class AdminMenuControllerTest extends TestCase {

	private const SCREEN_ID = 'woocommerce_page_melhor-integrador';

	private Container $container;

	private AdminMenuController $controller;

	protected function setUp(): void {
		parent::setUp();
		$this->container  = new Container();
		$this->controller = new AdminMenuController( $this->container );
		Functions\stubEscapeFunctions();
	}

	public function test_register_hooks_menu_styles_and_body_class(): void {
		$this->controller->register();

		self::assertSame( 20, has_action( 'admin_menu', array( $this->controller, 'addSubmenuPage' ) ) );
		self::assertNotFalse( has_action( 'admin_head', array( $this->controller, 'addAdminStyles' ) ) );
		self::assertNotFalse( has_filter( 'admin_body_class', array( $this->controller, 'addBodyClass' ) ) );
	}

	public function test_add_submenu_page_uses_default_configuration(): void {
		Functions\expect( 'add_submenu_page' )
			->once()
			->with(
				'woocommerce',
				'Melhor Envio',
				'Melhor Envio',
				'manage_options',
				'melhor-integrador',
				array( $this->controller, 'renderPage' )
			);

		$this->controller->addSubmenuPage();
	}

	public function test_add_submenu_page_uses_custom_configuration(): void {
		$controller = new AdminMenuController( $this->container, 'Page', 'Menu', 'edit_posts', 'custom-slug', 'tools.php' );

		Functions\expect( 'add_submenu_page' )
			->once()
			->with( 'tools.php', 'Page', 'Menu', 'edit_posts', 'custom-slug', array( $controller, 'renderPage' ) );

		$controller->addSubmenuPage();

		self::assertSame( 'custom-slug', $controller->getMenuSlug() );
	}

	public function test_get_menu_slug_defaults_to_melhor_integrador(): void {
		self::assertSame( 'melhor-integrador', $this->controller->getMenuSlug() );
	}

	public function test_render_page_renders_admin_page_with_container_services(): void {
		$resolved = array();
		$this->container->singleton(
			SecretService::class,
			function () use ( &$resolved ) {
				$resolved[] = SecretService::class;
				return new SecretService();
			}
		);
		$this->container->singleton(
			SignatureService::class,
			function () use ( &$resolved ) {
				$resolved[] = SignatureService::class;
				return new SignatureService();
			}
		);
		Functions\when( 'get_option' )->alias(
			function ( $key, $default = false ) {
				return $default;
			}
		);
		Functions\when( 'get_transient' )->justReturn( 'sig' );

		ob_start();
		try {
			$this->controller->renderPage();
		} finally {
			$output = (string) ob_get_clean();
		}

		self::assertSame( array( SecretService::class, SignatureService::class ), $resolved );
		self::assertStringContainsString( 'id="melhor-envio-integrador-container"', $output );
		self::assertStringContainsString( 'var signature = "sig";', $output );
	}

	public function test_add_admin_styles_removes_notices_and_prints_styles_on_plugin_screen(): void {
		Functions\when( 'get_current_screen' )->justReturn( (object) array( 'id' => self::SCREEN_ID ) );
		Functions\expect( 'remove_all_actions' )->once()->with( 'admin_notices' );
		Functions\expect( 'remove_all_actions' )->once()->with( 'all_admin_notices' );

		ob_start();
		try {
			$this->controller->addAdminStyles();
		} finally {
			$output = (string) ob_get_clean();
		}

		self::assertStringContainsString( '<style>', $output );
		self::assertStringContainsString( '.melhor-envio-integrador-page.' . self::SCREEN_ID . ' #wpbody-content', $output );
	}

	/**
	 * @dataProvider otherScreens
	 */
	public function test_add_admin_styles_does_nothing_outside_plugin_screen( ?object $screen ): void {
		Functions\when( 'get_current_screen' )->justReturn( $screen );
		Functions\expect( 'remove_all_actions' )->never();

		ob_start();
		try {
			$this->controller->addAdminStyles();
		} finally {
			$output = (string) ob_get_clean();
		}

		self::assertSame( '', $output );
	}

	public function test_add_body_class_appends_class_on_plugin_screen(): void {
		Functions\when( 'get_current_screen' )->justReturn( (object) array( 'id' => self::SCREEN_ID ) );

		self::assertSame( 'folded melhor-envio-integrador-page', $this->controller->addBodyClass( 'folded' ) );
	}

	/**
	 * @dataProvider otherScreens
	 */
	public function test_add_body_class_keeps_classes_outside_plugin_screen( ?object $screen ): void {
		Functions\when( 'get_current_screen' )->justReturn( $screen );

		self::assertSame( 'folded', $this->controller->addBodyClass( 'folded' ) );
	}

	public function otherScreens(): array {
		return array(
			'no screen'    => array( null ),
			'other screen' => array( (object) array( 'id' => 'woocommerce_page_wc-settings' ) ),
		);
	}
}
