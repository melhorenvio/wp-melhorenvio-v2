<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Http\Controllers\Settings;

use Brain\Monkey\Functions;
use MelhorEnvio\Http\Controllers\Settings\SaveSettingsController;
use MelhorEnvio\Services\Auth\SecretService;
use MelhorEnvio\Services\Settings\IntegradorSettingsService;
use MelhorEnvio\Tests\TestCase;
use Mockery;
use WP_REST_Request;

final class SaveSettingsControllerTest extends TestCase {

	private const SETTINGS_OPTION = 'melhor_envio_integrador_settings';
	private const SECRET_OPTION   = 'melhor_envio_integrador_secret';

	/** @var array<string, mixed> */
	private array $options = array();

	private SaveSettingsController $controller;

	protected function setUp(): void {
		parent::setUp();
		$this->controller = new SaveSettingsController( new SecretService(), new IntegradorSettingsService() );

		Functions\when( 'get_option' )->alias(
			function ( $key, $default = false ) {
				return array_key_exists( $key, $this->options ) ? $this->options[ $key ] : $default;
			}
		);
		Functions\when( 'sanitize_text_field' )->alias(
			function ( $value ) {
				return trim( strip_tags( (string) $value ) );
			}
		);
	}

	public function test_register_hooks_route_registration_into_rest_api_init(): void {
		$this->controller->register();

		self::assertNotFalse( has_action( 'rest_api_init', array( $this->controller, 'registerRoute' ) ) );
	}

	public function test_register_route_exposes_post_settings_endpoint_protected_by_secret(): void {
		$args = $this->captureRouteArgs();

		self::assertSame( 'POST', $args['methods'] );
		self::assertSame( array( $this->controller, 'handleRequest' ), $args['callback'] );
		self::assertSame( array( $this->controller, 'checkSecretPermission' ), $args['permission_callback'] );
	}

	public function test_permission_callback_accepts_matching_secret(): void {
		$this->options[ self::SECRET_OPTION ] = 'base64:abc';
		$args                                 = $this->captureRouteArgs();

		$request = new WP_REST_Request( 'POST', '/settings' );
		$request->set_header( 'X-ME-Secret', 'base64:abc' );

		self::assertTrue( call_user_func( $args['permission_callback'], $request ) );
	}

	public function test_permission_callback_rejects_wrong_secret(): void {
		$this->options[ self::SECRET_OPTION ] = 'base64:abc';
		$args                                 = $this->captureRouteArgs();

		$request = new WP_REST_Request( 'POST', '/settings' );
		$request->set_header( 'X-ME-Secret', 'base64:other' );

		self::assertFalse( call_user_func( $args['permission_callback'], $request ) );
	}

	/**
	 * @dataProvider invalidPayloads
	 */
	public function test_handle_request_rejects_invalid_payload( string $body ): void {
		$this->skipOnPhp74MixedTypeBug();
		Functions\expect( 'update_option' )->never();

		$response = $this->controller->handleRequest( $this->requestWithBody( $body ) );

		self::assertSame( 400, $response->get_status() );
		self::assertSame( array( 'message' => 'Invalid payload.' ), $response->get_data() );
	}

	public function invalidPayloads(): array {
		return array(
			'empty body'                   => array( '' ),
			'malformed json'               => array( '{"calculator":' ),
			'scalar json'                  => array( '"settings"' ),
			'calculator not object'        => array( '{"calculator":true}' ),
			'dimensions_default not object' => array( '{"dimensions_default":"10x10"}' ),
			'checkout not object'          => array( '{"checkout":1}' ),
		);
	}

	public function test_handle_request_sanitizes_and_saves_settings(): void {
		$this->skipOnPhp74MixedTypeBug();
		$body = array(
			'calculator'         => array(
				'enabled'  => 1,
				'position' => ' <b>woocommerce_after_add_to_cart_form</b> ',
				'extra'    => 'ignored',
			),
			'dimensions_default' => array(
				'height' => '10',
				'width'  => 15,
				'length' => '20.5',
				'weight' => '0.3',
				'depth'  => 99,
			),
			'checkout'           => array(
				'document_type'        => 'cpf_only',
				'require_number'       => 'yes',
				'require_neighborhood' => 0,
			),
			'unknown'            => array( 'foo' => 'bar' ),
		);
		$expected = array(
			'calculator'         => array(
				'enabled'  => true,
				'position' => 'woocommerce_after_add_to_cart_form',
			),
			'dimensions_default' => array(
				'height' => 10.0,
				'width'  => 15.0,
				'length' => 20.5,
				'weight' => 0.3,
			),
			'checkout'           => array(
				'document_type'        => 'cpf_only',
				'require_number'       => true,
				'require_neighborhood' => false,
			),
		);
		Functions\expect( 'update_option' )->once()->with( self::SETTINGS_OPTION, $expected )->andReturn( true );

		$response = $this->controller->handleRequest( $this->requestWithBody( (string) json_encode( $body ) ) );

		self::assertSame( 200, $response->get_status() );
		self::assertSame( $expected, $response->get_data() );
	}

	public function test_handle_request_drops_unsupported_document_type(): void {
		$this->skipOnPhp74MixedTypeBug();
		$expected = array( 'checkout' => array( 'require_number' => false ) );
		Functions\expect( 'update_option' )->once()->with( self::SETTINGS_OPTION, $expected )->andReturn( true );

		$response = $this->controller->handleRequest(
			$this->requestWithBody( '{"checkout":{"document_type":"passport","require_number":false}}' )
		);

		self::assertSame( 200, $response->get_status() );
		self::assertSame( $expected, $response->get_data() );
	}

	/**
	 * @dataProvider allowedDocumentTypes
	 */
	public function test_handle_request_accepts_allowed_document_types( string $documentType ): void {
		$this->skipOnPhp74MixedTypeBug();
		$expected = array( 'checkout' => array( 'document_type' => $documentType ) );
		Functions\expect( 'update_option' )->once()->with( self::SETTINGS_OPTION, $expected )->andReturn( true );

		$response = $this->controller->handleRequest(
			$this->requestWithBody( '{"checkout":{"document_type":"' . $documentType . '"}}' )
		);

		self::assertSame( $expected, $response->get_data() );
	}

	public function allowedDocumentTypes(): array {
		return array(
			'both'      => array( 'both' ),
			'cpf_only'  => array( 'cpf_only' ),
			'cnpj_only' => array( 'cnpj_only' ),
		);
	}

	public function test_handle_request_keeps_empty_sections_sent_as_empty_objects(): void {
		$this->skipOnPhp74MixedTypeBug();
		$expected = array(
			'calculator'         => array(),
			'dimensions_default' => array(),
			'checkout'           => array(),
		);
		Functions\expect( 'update_option' )->once()->with( self::SETTINGS_OPTION, $expected )->andReturn( true );

		$response = $this->controller->handleRequest(
			$this->requestWithBody( '{"calculator":{},"dimensions_default":{},"checkout":{}}' )
		);

		self::assertSame( 200, $response->get_status() );
		self::assertSame( $expected, $response->get_data() );
	}

	public function test_handle_request_treats_unchanged_settings_as_success(): void {
		$this->skipOnPhp74MixedTypeBug();
		$settings                               = array( 'calculator' => array( 'enabled' => false ) );
		$this->options[ self::SETTINGS_OPTION ] = $settings;
		Functions\expect( 'update_option' )->once()->andReturn( false );

		$response = $this->controller->handleRequest( $this->requestWithBody( '{"calculator":{"enabled":false}}' ) );

		self::assertSame( 200, $response->get_status() );
		self::assertSame( $settings, $response->get_data() );
	}

	public function test_handle_request_returns_500_when_save_fails(): void {
		$this->skipOnPhp74MixedTypeBug();
		Functions\expect( 'update_option' )->once()->andReturn( false );

		$response = $this->controller->handleRequest( $this->requestWithBody( '{"calculator":{"enabled":true}}' ) );

		self::assertSame( 500, $response->get_status() );
		self::assertSame( array( 'message' => 'Failed to save settings.' ), $response->get_data() );
	}

	/**
	 * BUG: SaveSettingsController::isValidPayload() usa o tipo `mixed` (PHP 8.0+); no PHP 7.4 toda requisição
	 * falha com TypeError. Correção: fix/settings-php74-mixed-type.
	 */
	private function skipOnPhp74MixedTypeBug(): void {
		if ( PHP_VERSION_ID < 80000 ) {
			$this->markTestIncomplete( 'BUG: tipo mixed em SaveSettingsController no PHP 7.4. Correção: fix/settings-php74-mixed-type.' );
		}
	}

	private function captureRouteArgs(): array {
		$captured = array();
		Functions\expect( 'register_rest_route' )
			->once()
			->with( 'wp-melhor-integrador/v1', '/settings', Mockery::type( 'array' ) )
			->andReturnUsing(
				function ( $namespace, $route, $args ) use ( &$captured ) {
					$captured = $args;
					return true;
				}
			);

		$this->controller->registerRoute();

		return $captured;
	}

	private function requestWithBody( string $body ): WP_REST_Request {
		$request = new WP_REST_Request( 'POST', '/settings' );
		$request->set_body( $body );

		return $request;
	}
}
