<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Services\Admin;

use Brain\Monkey\Functions;
use MelhorEnvio\Services\Admin\PluginModeService;
use MelhorEnvio\Tests\TestCase;
use Mockery;

require_once dirname( __DIR__, 3 ) . '/Stubs/Extra/PluginModeServiceTest.php';

final class PluginModeServiceTest extends TestCase {

	/** @var mixed */
	private $originalWpdb;

	protected function setUp(): void {
		parent::setUp();
		$this->originalWpdb = $GLOBALS['wpdb'] ?? null;
		\WC_Cache_Helper::$calls = array();
	}

	protected function tearDown(): void {
		$GLOBALS['wpdb']         = $this->originalWpdb;
		\WC_Cache_Helper::$calls = array();
		parent::tearDown();
	}

	public function test_get_mode_defaults_to_legacy(): void {
		Functions\expect( 'get_option' )->once()->with( 'melhor_envio_ui_mode', 'legacy' )->andReturn( 'legacy' );

		self::assertSame( 'legacy', PluginModeService::getMode() );
	}

	public function test_get_mode_returns_stored_mode(): void {
		Functions\when( 'get_option' )->justReturn( 'integrador' );

		self::assertSame( 'integrador', PluginModeService::getMode() );
	}

	/**
	 * @dataProvider modes
	 */
	public function test_is_integrador_mode( string $mode, bool $expected ): void {
		Functions\when( 'get_option' )->justReturn( $mode );

		self::assertSame( $expected, PluginModeService::isIntegradorMode() );
	}

	public function modes(): array {
		return array(
			'integrador' => array( 'integrador', true ),
			'legacy'     => array( 'legacy', false ),
			'uppercase'  => array( 'INTEGRADOR', false ),
			'empty'      => array( '', false ),
		);
	}

	public function test_set_mode_integrador_saves_option_and_enables_shipping_method(): void {
		Functions\expect( 'update_option' )->once()->with( 'melhor_envio_ui_mode', 'integrador' );
		$this->expectShippingMethodToggle( 1 );

		PluginModeService::setMode( 'integrador' );

		self::assertSame( array( array( 'shipping', true ) ), \WC_Cache_Helper::$calls );
	}

	/**
	 * @dataProvider nonIntegradorModes
	 */
	public function test_set_mode_other_than_integrador_disables_shipping_method( string $mode ): void {
		Functions\expect( 'update_option' )->once()->with( 'melhor_envio_ui_mode', $mode );
		$this->expectShippingMethodToggle( 0 );

		PluginModeService::setMode( $mode );

		self::assertSame( array( array( 'shipping', true ) ), \WC_Cache_Helper::$calls );
	}

	public function nonIntegradorModes(): array {
		return array(
			'legacy' => array( 'legacy' ),
			'empty'  => array( '' ),
		);
	}

	private function expectShippingMethodToggle( int $isEnabled ): void {
		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->expects( 'update' )->with(
			'wp_woocommerce_shipping_zone_methods',
			array( 'is_enabled' => $isEnabled ),
			array( 'method_id' => 'melhor_envio' )
		);

		$GLOBALS['wpdb'] = $wpdb;
	}
}
