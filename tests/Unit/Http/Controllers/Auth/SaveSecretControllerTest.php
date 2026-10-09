<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Http\Controllers\Auth;

use Brain\Monkey\Functions;
use MelhorEnvio\Http\Controllers\Auth\SaveSecretController;
use MelhorEnvio\Services\Auth\SecretService;
use MelhorEnvio\Services\Auth\SignatureService;
use MelhorEnvio\Services\Shipping\ShippingZoneService;
use MelhorEnvio\Tests\TestCase;
use Mockery;
use WC_Shipping_Zone;
use WC_Shipping_Zones;
use WP_REST_Request;

final class SaveSecretControllerTest extends TestCase {

	private const SECRET_OPTION      = 'melhor_envio_integrador_secret';
	private const SIGNATURE_TRANSIENT = 'melhor_envio_integrador_signature';
	private const VALID_SECRET       = 'base64:c2VjcmV0LWtleQ==';

	private SaveSecretController $controller;

	protected function setUp(): void {
		parent::setUp();
		$this->controller = new SaveSecretController(
			new SecretService(),
			new SignatureService(),
			new ShippingZoneService()
		);
	}

	public function test_register_hooks_route_registration_into_rest_api_init(): void {
		$this->controller->register();

		self::assertNotFalse( has_action( 'rest_api_init', array( $this->controller, 'registerRoute' ) ) );
	}

	public function test_register_route_exposes_post_secret_endpoint_protected_by_signature(): void {
		$args = $this->captureRouteArgs();

		self::assertSame( 'POST', $args['methods'] );
		self::assertSame( array( $this->controller, 'handleRequest' ), $args['callback'] );
		self::assertSame( array( $this->controller, 'checkSignaturePermission' ), $args['permission_callback'] );
		self::assertSame(
			array(
				'secret' => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
			$args['args']
		);
	}

	public function test_permission_callback_accepts_and_consumes_valid_signature(): void {
		$args = $this->captureRouteArgs();
		Functions\when( 'get_transient' )->justReturn( 'sig-123' );
		Functions\expect( 'delete_transient' )->once()->with( self::SIGNATURE_TRANSIENT )->andReturn( true );

		$request = new WP_REST_Request( 'POST', '/secret' );
		$request->set_header( 'X-ME-Signature', 'sig-123' );

		self::assertTrue( call_user_func( $args['permission_callback'], $request ) );
	}

	public function test_permission_callback_rejects_wrong_signature(): void {
		$args = $this->captureRouteArgs();
		Functions\when( 'get_transient' )->justReturn( 'sig-123' );
		Functions\expect( 'delete_transient' )->never();

		$request = new WP_REST_Request( 'POST', '/secret' );
		$request->set_header( 'X-ME-Signature', 'sig-999' );

		self::assertFalse( call_user_func( $args['permission_callback'], $request ) );
	}

	public function test_permission_callback_rejects_missing_signature_header(): void {
		$args = $this->captureRouteArgs();
		Functions\expect( 'get_transient' )->never();

		self::assertFalse( call_user_func( $args['permission_callback'], new WP_REST_Request( 'POST', '/secret' ) ) );
	}

	/**
	 * @dataProvider missingSecrets
	 *
	 * @param mixed $secret
	 */
	public function test_handle_request_requires_secret( $secret ): void {
		Functions\expect( 'update_option' )->never();

		$response = $this->controller->handleRequest( $this->requestWithSecret( $secret ) );

		self::assertSame( 400, $response->get_status() );
		self::assertSame( array( 'message' => 'Secret is required.' ), $response->get_data() );
	}

	public function missingSecrets(): array {
		return array(
			'null'         => array( null ),
			'empty string' => array( '' ),
		);
	}

	/**
	 * @dataProvider malformedSecrets
	 */
	public function test_handle_request_rejects_malformed_secret( string $secret ): void {
		Functions\expect( 'update_option' )->never();

		$response = $this->controller->handleRequest( $this->requestWithSecret( $secret ) );

		self::assertSame( 400, $response->get_status() );
		self::assertSame( array( 'message' => 'Invalid secret format.' ), $response->get_data() );
	}

	public function malformedSecrets(): array {
		return array(
			'missing prefix'       => array( 'c2VjcmV0LWtleQ==' ),
			'wrong prefix case'    => array( 'BASE64:c2VjcmV0LWtleQ==' ),
			'prefix only'          => array( 'base64:' ),
			'invalid base64 chars' => array( 'base64:not*valid*base64' ),
		);
	}

	public function test_handle_request_saves_secret_and_registers_shipping_method(): void {
		Functions\when( 'get_option' )->justReturn( false );
		Functions\expect( 'update_option' )->once()->with( self::SECRET_OPTION, self::VALID_SECRET )->andReturn( true );

		$response = $this->controller->handleRequest( $this->requestWithSecret( self::VALID_SECRET ) );

		self::assertSame( 200, $response->get_status() );
		self::assertSame( array( 'message' => 'Secret saved successfully.' ), $response->get_data() );
		$zone = end( WC_Shipping_Zone::$instances );
		self::assertSame( array( 'melhor_envio' ), $zone->added_methods );
	}

	public function test_handle_request_does_not_duplicate_existing_shipping_method(): void {
		WC_Shipping_Zone::$records[3] = array(
			'locations' => array( (object) array( 'type' => 'country', 'code' => 'BR' ) ),
			'methods'   => array( (object) array( 'id' => 'melhor_envio', 'instance_id' => 9 ) ),
		);
		WC_Shipping_Zones::$zones     = array( array( 'id' => 3 ) );
		Functions\when( 'get_option' )->justReturn( false );
		Functions\when( 'update_option' )->justReturn( true );

		$response = $this->controller->handleRequest( $this->requestWithSecret( self::VALID_SECRET ) );

		self::assertSame( 200, $response->get_status() );
		self::assertSame( array(), WC_Shipping_Zone::$instances[0]->added_methods );
	}

	public function test_handle_request_returns_500_when_secret_cannot_be_saved(): void {
		Functions\when( 'get_option' )->justReturn( false );
		Functions\expect( 'update_option' )->once()->andReturn( false );

		$response = $this->controller->handleRequest( $this->requestWithSecret( self::VALID_SECRET ) );

		self::assertSame( 500, $response->get_status() );
		self::assertSame( array( 'message' => 'Failed to save secret.' ), $response->get_data() );
		self::assertSame( array(), WC_Shipping_Zone::$instances, 'Shipping method must not be registered on failure.' );
	}

	public function test_handle_request_succeeds_when_same_secret_is_resent(): void {
		$this->markTestIncomplete(
			'BUG: update_option retorna false quando o valor não muda e o controller responde 500. '
			. 'Correção: fix/secret-resend-idempotent.'
		);

		Functions\when( 'get_option' )->justReturn( self::VALID_SECRET );
		Functions\when( 'update_option' )->justReturn( false );

		$response = $this->controller->handleRequest( $this->requestWithSecret( self::VALID_SECRET ) );

		self::assertSame( 200, $response->get_status() );
	}

	private function captureRouteArgs(): array {
		$captured = array();
		Functions\expect( 'register_rest_route' )
			->once()
			->with( 'wp-melhor-integrador/v1', '/secret', Mockery::type( 'array' ) )
			->andReturnUsing(
				function ( $namespace, $route, $args ) use ( &$captured ) {
					$captured = $args;
					return true;
				}
			);

		$this->controller->registerRoute();

		return $captured;
	}

	/**
	 * @param mixed $secret
	 */
	private function requestWithSecret( $secret ): WP_REST_Request {
		$request = new WP_REST_Request( 'POST', '/secret' );
		$request->set_param( 'secret', $secret );

		return $request;
	}
}
