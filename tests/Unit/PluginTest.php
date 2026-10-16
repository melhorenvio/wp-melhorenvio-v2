<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit;

use Brain\Monkey\Functions;
use MelhorEnvio\Core\Container;
use MelhorEnvio\Hooks\HookManager;
use MelhorEnvio\Http\Controllers\Admin\AdminMenuController;
use MelhorEnvio\Plugin;
use MelhorEnvio\Services\Auth\SecretService;
use MelhorEnvio\Tests\TestCase;

final class PluginTest extends TestCase {

	private Plugin $plugin;

	protected function setUp(): void {
		parent::setUp();

		// Every option falls back to its default: legacy mode, so mode-dependent controllers stay idle.
		Functions\when( 'get_option' )->alias(
			static function ( $name, $default = false ) {
				return $default;
			}
		);

		$this->plugin = new Plugin();
	}

	public function test_boot_exposes_container(): void {
		$this->plugin->boot();

		self::assertInstanceOf( Container::class, $this->plugin->getContainer() );
	}

	public function test_boot_registers_core_services_as_singletons(): void {
		$this->plugin->boot();
		$container = $this->plugin->getContainer();

		self::assertSame( $container->get( SecretService::class ), $container->get( SecretService::class ) );
		self::assertSame( $container->get( HookManager::class ), $container->get( HookManager::class ) );
	}

	public function test_boot_registers_hooks_through_the_container_hook_manager(): void {
		$this->plugin->boot();
		$container = $this->plugin->getContainer();

		self::assertSame( 10, has_action( 'wp_logout', array( $container->get( HookManager::class ), 'onUserLogout' ) ) );
		self::assertSame( 20, has_action( 'admin_menu', array( $container->get( AdminMenuController::class ), 'addSubmenuPage' ) ) );
		self::assertNotFalse( has_filter( 'woocommerce_shipping_methods' ) );
		self::assertNotFalse( has_action( 'woocommerce_order_note_added' ) );
	}

	public function test_boot_creates_a_fresh_container_each_time(): void {
		$this->plugin->boot();
		$first = $this->plugin->getContainer();

		$this->plugin->boot();

		self::assertNotSame( $first, $this->plugin->getContainer() );
	}
}
