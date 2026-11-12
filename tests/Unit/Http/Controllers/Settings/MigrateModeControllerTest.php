<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Http\Controllers\Settings;

use Brain\Monkey\Functions;
use MelhorEnvio\Http\Controllers\Settings\MigrateModeController;
use MelhorEnvio\Services\Auth\SecretService;
use MelhorEnvio\Tests\TestCase;
use Mockery;
use WP_REST_Request;

require_once dirname( __DIR__, 4 ) . '/Stubs/Extra/PluginModeServiceTest.php';

final class MigrateModeControllerTest extends TestCase {

	private const SECRET_OPTION = 'melhor_envio_integrador_secret';

	private MigrateModeController $controller;

	/** @var mixed */
	private $originalWpdb;

	protected function setUp(): void {
		parent::setUp();
		$this->originalWpdb      = $GLOBALS['wpdb'] ?? null;
		\WC_Cache_Helper::$calls = array();
		$this->controller        = new MigrateModeController( new SecretService() );
	}

	protected function tearDown(): void {
		$GLOBALS['wpdb']         = $this->originalWpdb;
		\WC_Cache_Helper::$calls = array();
		parent::tearDown();
	}

	public function test_register_hooks_route_registration_into_rest_api_init(): void {
		$this->controller->register();

		self::assertNotFalse( has_action( 'rest_api_init', array( $this->controller, 'registerRoute' ) ) );
	}

	public function test_register_route_exposes_post_endpoint_protected_by_secret(): void {
		$args = $this->captureRouteArgs();

		self::assertSame( 'POST', $args['methods'] );
		self::assertSame( array( $this->controller, 'handleRequest' ), $args['callback'] );
		self::assertSame( array( $this->controller, 'checkSecretPermission' ), $args['permission_callback'] );
	}

	public function test_permission_callback_accepts_matching_secret(): void {
		$args = $this->captureRouteArgs();
		Functions\expect( 'get_option' )->with( self::SECRET_OPTION )->andReturn( 'base64:abc' );

		self::assertTrue( call_user_func( $args['permission_callback'], $this->requestWithSecret( 'base64:abc' ) ) );
	}

	public function test_permission_callback_rejects_wrong_secret(): void {
		$args = $this->captureRouteArgs();
		Functions\when( 'get_option' )->justReturn( 'base64:abc' );

		self::assertFalse( call_user_func( $args['permission_callback'], $this->requestWithSecret( 'base64:xyz' ) ) );
	}

	public function test_permission_callback_rejects_missing_secret_header(): void {
		$args = $this->captureRouteArgs();
		Functions\when( 'get_option' )->justReturn( 'base64:abc' );

		self::assertFalse( call_user_func( $args['permission_callback'], new WP_REST_Request( 'POST', '/migrate' ) ) );
	}

	public function test_permission_callback_rejects_when_no_secret_is_stored(): void {
		$args = $this->captureRouteArgs();
		Functions\when( 'get_option' )->justReturn( false );

		self::assertFalse( call_user_func( $args['permission_callback'], $this->requestWithSecret( 'base64:abc' ) ) );
	}

	public function test_handle_request_switches_to_integrador_mode(): void {
		Functions\expect( 'update_option' )->once()->with( 'melhor_envio_ui_mode', 'integrador' );
		Functions\expect( 'delete_option' )->once()->with( 'wp_option_notices_melhor_envio' );
		$this->expectShippingMethodEnabled( 1 );

		$response = $this->controller->handleRequest();

		self::assertSame( 200, $response->get_status() );
		self::assertSame( array( 'mode' => 'integrador' ), $response->get_data() );
		self::assertSame( array( array( 'shipping', true ) ), \WC_Cache_Helper::$calls );
	}

	public function test_handle_request_is_idempotent(): void {
		Functions\expect( 'update_option' )->twice()->with( 'melhor_envio_ui_mode', 'integrador' );
		Functions\expect( 'delete_option' )->twice()->with( 'wp_option_notices_melhor_envio' );
		$this->expectShippingMethodEnabled( 2 );

		$first  = $this->controller->handleRequest();
		$second = $this->controller->handleRequest();

		self::assertSame( $first->get_data(), $second->get_data() );
		self::assertSame( 200, $second->get_status() );
	}

	private function expectShippingMethodEnabled( int $times ): void {
		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->expects( 'update' )
			->times( $times )
			->with(
				'wp_woocommerce_shipping_zone_methods',
				array( 'is_enabled' => 1 ),
				array( 'method_id' => 'melhor_envio' )
			);

		$GLOBALS['wpdb'] = $wpdb;
	}

	private function captureRouteArgs(): array {
		$captured = array();
		Functions\expect( 'register_rest_route' )
			->once()
			->with( 'wp-melhor-integrador/v1', '/migrate', Mockery::type( 'array' ) )
			->andReturnUsing(
				function ( $namespace, $route, $args ) use ( &$captured ) {
					$captured = $args;
					return true;
				}
			);

		$this->controller->registerRoute();

		return $captured;
	}

	private function requestWithSecret( string $secret ): WP_REST_Request {
		$request = new WP_REST_Request( 'POST', '/migrate' );
		$request->set_header( 'X-ME-Secret', $secret );

		return $request;
	}
}
