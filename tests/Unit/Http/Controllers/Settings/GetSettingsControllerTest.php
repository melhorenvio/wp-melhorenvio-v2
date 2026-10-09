<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Http\Controllers\Settings;

use Brain\Monkey\Functions;
use MelhorEnvio\Http\Controllers\Settings\GetSettingsController;
use MelhorEnvio\Services\Auth\SecretService;
use MelhorEnvio\Services\Settings\IntegradorSettingsService;
use MelhorEnvio\Tests\TestCase;
use WP_REST_Request;

final class GetSettingsControllerTest extends TestCase {

	private const SETTINGS_OPTION = 'melhor_envio_integrador_settings';
	private const SECRET_OPTION   = 'melhor_envio_integrador_secret';

	/** @var array<string, mixed> */
	private array $options = array();

	private GetSettingsController $controller;

	protected function setUp(): void {
		parent::setUp();
		$this->controller = new GetSettingsController( new SecretService(), new IntegradorSettingsService() );

		Functions\when( 'get_option' )->alias(
			function ( $key, $default = false ) {
				return array_key_exists( $key, $this->options ) ? $this->options[ $key ] : $default;
			}
		);
	}

	public function test_register_hooks_route_registration_into_rest_api_init(): void {
		$this->controller->register();

		self::assertNotFalse( has_action( 'rest_api_init', array( $this->controller, 'registerRoute' ) ) );
	}

	public function test_register_route_exposes_get_settings_endpoint_protected_by_secret(): void {
		$args = $this->captureRouteArgs();

		self::assertSame( 'GET', $args['methods'] );
		self::assertSame( array( $this->controller, 'handleRequest' ), $args['callback'] );
		self::assertSame( array( $this->controller, 'checkSecretPermission' ), $args['permission_callback'] );
	}

	public function test_permission_callback_accepts_matching_secret(): void {
		$this->options[ self::SECRET_OPTION ] = 'base64:abc';
		$args                                 = $this->captureRouteArgs();

		self::assertTrue( call_user_func( $args['permission_callback'], $this->requestWithSecret( 'base64:abc' ) ) );
	}

	/**
	 * @dataProvider rejectedSecrets
	 */
	public function test_permission_callback_rejects_invalid_secret( ?string $header ): void {
		$this->options[ self::SECRET_OPTION ] = 'base64:abc';
		$args                                 = $this->captureRouteArgs();

		self::assertFalse( call_user_func( $args['permission_callback'], $this->requestWithSecret( $header ) ) );
	}

	public function rejectedSecrets(): array {
		return array(
			'missing header' => array( null ),
			'wrong secret'   => array( 'base64:xyz' ),
		);
	}

	public function test_handle_request_returns_stored_settings(): void {
		$settings                               = array(
			'calculator'         => array( 'enabled' => true, 'position' => 'woocommerce_after_add_to_cart_form' ),
			'dimensions_default' => array( 'height' => 2.0 ),
		);
		$this->options[ self::SETTINGS_OPTION ] = $settings;

		$response = $this->controller->handleRequest( new WP_REST_Request( 'GET', '/settings' ) );

		self::assertSame( 200, $response->get_status() );
		self::assertSame( $settings, $response->get_data() );
	}

	public function test_handle_request_returns_empty_settings_when_nothing_configured(): void {
		$response = $this->controller->handleRequest( new WP_REST_Request( 'GET', '/settings' ) );

		self::assertSame( 200, $response->get_status() );
		self::assertSame( array(), $response->get_data() );
	}

	private function captureRouteArgs(): array {
		$captured = array();
		Functions\expect( 'register_rest_route' )
			->once()
			->with( 'wp-melhor-integrador/v1', '/settings', \Mockery::type( 'array' ) )
			->andReturnUsing(
				function ( $namespace, $route, $args ) use ( &$captured ) {
					$captured = $args;
					return true;
				}
			);

		$this->controller->registerRoute();

		return $captured;
	}

	private function requestWithSecret( ?string $secret ): WP_REST_Request {
		$request = new WP_REST_Request( 'GET', '/settings' );
		if ( $secret !== null ) {
			$request->set_header( 'X-ME-Secret', $secret );
		}

		return $request;
	}
}
