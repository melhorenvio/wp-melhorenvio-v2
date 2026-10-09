<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Http\Controllers\Auth;

use Brain\Monkey\Functions;
use MelhorEnvio\Http\Controllers\Auth\DisconnectController;
use MelhorEnvio\Services\Auth\SecretService;
use MelhorEnvio\Services\Shipping\ShippingZoneService;
use MelhorEnvio\Tests\TestCase;
use Mockery;
use WC_Data_Store;
use WC_Shipping_Zone;
use WC_Shipping_Zones;
use WC_Webhook;
use WP_REST_Request;

final class DisconnectControllerTest extends TestCase {

	private const SECRET_OPTION = 'melhor_envio_integrador_secret';
	private const TOKEN_OPTION  = 'melhor_envio_integrador_quotation_token';

	private DisconnectController $controller;

	protected function setUp(): void {
		parent::setUp();
		$this->controller = new DisconnectController( new SecretService(), new ShippingZoneService() );
	}

	public function test_register_hooks_route_registration_into_rest_api_init(): void {
		$this->controller->register();

		self::assertNotFalse( has_action( 'rest_api_init', array( $this->controller, 'registerRoute' ) ) );
	}

	public function test_register_route_exposes_delete_endpoint_protected_by_secret(): void {
		$args = $this->captureRouteArgs();

		self::assertSame( 'DELETE', $args['methods'] );
		self::assertSame( array( $this->controller, 'handleRequest' ), $args['callback'] );
		self::assertSame( array( $this->controller, 'checkSecretPermission' ), $args['permission_callback'] );
	}

	public function test_permission_callback_accepts_matching_secret(): void {
		$args = $this->captureRouteArgs();
		Functions\when( 'get_option' )->justReturn( 'base64:abc' );

		self::assertTrue( call_user_func( $args['permission_callback'], $this->requestWithSecret( 'base64:abc' ) ) );
	}

	public function test_permission_callback_rejects_wrong_secret(): void {
		$args = $this->captureRouteArgs();
		Functions\when( 'get_option' )->justReturn( 'base64:abc' );

		self::assertFalse( call_user_func( $args['permission_callback'], $this->requestWithSecret( 'base64:xyz' ) ) );
	}

	public function test_handle_request_clears_credentials_shipping_method_and_webhooks(): void {
		Functions\expect( 'delete_option' )->once()->with( self::SECRET_OPTION )->andReturn( true );
		Functions\expect( 'delete_option' )->once()->with( self::TOKEN_OPTION )->andReturn( true );

		WC_Shipping_Zone::$records[2] = array(
			'locations' => array( (object) array( 'type' => 'country', 'code' => 'BR' ) ),
			'methods'   => array(
				(object) array( 'id' => 'flat_rate', 'instance_id' => 5 ),
				(object) array( 'id' => 'melhor_envio', 'instance_id' => 6 ),
			),
		);
		WC_Shipping_Zones::$zones     = array( array( 'id' => 2 ) );

		$this->givenWebhooks(
			array(
				11 => 'https://webhook-wordpress-envios.melhorenvio.com.br/orders',
				12 => 'https://example.com/hooks/orders',
				13 => 'https://webhook.woocommerceenvios.com/orders',
			)
		);

		$response = $this->controller->handleRequest( new WP_REST_Request( 'DELETE', '/disconnect' ) );

		self::assertSame( 200, $response->get_status() );
		self::assertSame( array( 'message' => 'Disconnected successfully.' ), $response->get_data() );
		self::assertSame( array( 6 ), WC_Shipping_Zone::$instances[0]->deleted_method_instance_ids );
		self::assertSame( array( 11, 13 ), WC_Webhook::$deleted );
	}

	public function test_handle_request_keeps_unrelated_webhooks(): void {
		Functions\when( 'delete_option' )->justReturn( true );
		$this->givenWebhooks(
			array(
				21 => 'https://example.com/melhorenvio',
				22 => '',
			)
		);

		$response = $this->controller->handleRequest( new WP_REST_Request( 'DELETE', '/disconnect' ) );

		self::assertSame( 200, $response->get_status() );
		self::assertSame( array(), WC_Webhook::$deleted );
	}

	public function test_handle_request_succeeds_without_webhooks_or_zones(): void {
		Functions\when( 'delete_option' )->justReturn( true );
		$this->givenWebhooks( array() );

		$response = $this->controller->handleRequest( new WP_REST_Request( 'DELETE', '/disconnect' ) );

		self::assertSame( 200, $response->get_status() );
		self::assertSame( array(), WC_Webhook::$deleted );
		self::assertSame( array(), WC_Shipping_Zone::$instances );
	}

	public function test_handle_request_only_removes_method_from_brazil_zones(): void {
		Functions\when( 'delete_option' )->justReturn( true );
		WC_Shipping_Zone::$records[8] = array(
			'locations' => array( (object) array( 'type' => 'country', 'code' => 'PT' ) ),
			'methods'   => array( (object) array( 'id' => 'melhor_envio', 'instance_id' => 30 ) ),
		);
		WC_Shipping_Zones::$zones     = array( array( 'id' => 8 ) );
		$this->givenWebhooks( array() );

		$this->controller->handleRequest( new WP_REST_Request( 'DELETE', '/disconnect' ) );

		self::assertSame( array(), WC_Shipping_Zone::$instances[0]->deleted_method_instance_ids );
	}

	/**
	 * @param array<int, string> $deliveryUrls
	 */
	private function givenWebhooks( array $deliveryUrls ): void {
		foreach ( $deliveryUrls as $id => $url ) {
			WC_Webhook::$records[ $id ] = array( 'delivery_url' => $url );
		}

		$store = Mockery::mock();
		$store->allows( 'get_webhooks_ids' )->andReturn( array_keys( $deliveryUrls ) );
		WC_Data_Store::$stores['webhook'] = $store;
	}

	private function captureRouteArgs(): array {
		$captured = array();
		Functions\expect( 'register_rest_route' )
			->once()
			->with( 'wp-melhor-integrador/v1', '/disconnect', Mockery::type( 'array' ) )
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
		$request = new WP_REST_Request( 'DELETE', '/disconnect' );
		$request->set_header( 'X-ME-Secret', $secret );

		return $request;
	}
}
