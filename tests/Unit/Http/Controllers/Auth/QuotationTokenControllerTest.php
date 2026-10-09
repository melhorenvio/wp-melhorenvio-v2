<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Http\Controllers\Auth;

use Brain\Monkey\Functions;
use MelhorEnvio\Http\Controllers\Auth\QuotationTokenController;
use MelhorEnvio\Services\Auth\SecretService;
use MelhorEnvio\Services\Shipping\ShippingZoneService;
use MelhorEnvio\Tests\TestCase;
use Mockery;
use WC_Shipping_Zone;
use WC_Shipping_Zones;
use WP_REST_Request;

final class QuotationTokenControllerTest extends TestCase {

	private const TOKEN_OPTION  = 'melhor_envio_integrador_quotation_token';
	private const SECRET_OPTION = 'melhor_envio_integrador_secret';

	private QuotationTokenController $controller;

	protected function setUp(): void {
		parent::setUp();
		$this->controller = new QuotationTokenController( new SecretService(), new ShippingZoneService() );
	}

	public function test_register_hooks_route_registration_into_rest_api_init(): void {
		$this->controller->register();

		self::assertNotFalse( has_action( 'rest_api_init', array( $this->controller, 'registerRoute' ) ) );
	}

	public function test_register_route_exposes_post_endpoint_with_required_token_arg(): void {
		$args = $this->captureRouteArgs();

		self::assertSame( 'POST', $args['methods'] );
		self::assertSame( array( $this->controller, 'handleRequest' ), $args['callback'] );
		self::assertSame( array( $this->controller, 'checkSecretPermission' ), $args['permission_callback'] );
		self::assertSame(
			array(
				'quotation_token' => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
			$args['args']
		);
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

	public function test_permission_callback_rejects_when_no_secret_is_stored(): void {
		$args = $this->captureRouteArgs();
		Functions\when( 'get_option' )->justReturn( false );

		self::assertFalse( call_user_func( $args['permission_callback'], $this->requestWithSecret( 'base64:abc' ) ) );
	}

	public function test_handle_request_saves_token_and_registers_shipping_method_in_new_brazil_zone(): void {
		Functions\expect( 'update_option' )->once()->with( self::TOKEN_OPTION, 'token-123' );

		$response = $this->controller->handleRequest( $this->requestWithToken( 'token-123' ) );

		self::assertSame( 200, $response->get_status() );
		self::assertSame( array( 'message' => 'Quotation token saved successfully.' ), $response->get_data() );

		self::assertCount( 1, WC_Shipping_Zone::$instances );
		$zone = WC_Shipping_Zone::$instances[0];
		self::assertSame( 'Brasil', $zone->zone_name );
		self::assertTrue( $zone->saved );
		self::assertSame( array( 'melhor_envio' ), $zone->added_methods );
	}

	public function test_handle_request_adds_method_to_existing_brazil_zone(): void {
		WC_Shipping_Zone::$records[4] = array(
			'locations' => array( (object) array( 'type' => 'country', 'code' => 'BR' ) ),
			'methods'   => array( (object) array( 'id' => 'flat_rate', 'instance_id' => 1 ) ),
		);
		WC_Shipping_Zones::$zones     = array( array( 'id' => 4 ) );
		Functions\when( 'update_option' )->justReturn( true );

		$this->controller->handleRequest( $this->requestWithToken( 'token-123' ) );

		self::assertCount( 1, WC_Shipping_Zone::$instances );
		self::assertSame( array( 'melhor_envio' ), WC_Shipping_Zone::$instances[0]->added_methods );
	}

	private function captureRouteArgs(): array {
		$captured = array();
		Functions\expect( 'register_rest_route' )
			->once()
			->with( 'wp-melhor-integrador/v1', '/quotation-token', Mockery::type( 'array' ) )
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
		$request = new WP_REST_Request( 'POST', '/quotation-token' );
		$request->set_header( 'X-ME-Secret', $secret );

		return $request;
	}

	private function requestWithToken( string $token ): WP_REST_Request {
		$request = new WP_REST_Request( 'POST', '/quotation-token' );
		$request->set_param( 'quotation_token', $token );

		return $request;
	}
}
