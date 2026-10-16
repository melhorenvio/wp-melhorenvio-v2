<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Http\Controllers\Quotation;

use Brain\Monkey\Functions;
use MelhorEnvio\Http\Controllers\Quotation\QuotationController;
use MelhorEnvio\Services\Quotation\MelhorEnvioApiClientService;
use MelhorEnvio\Services\Quotation\PostalCodeLocationClientService;
use MelhorEnvio\Services\Settings\IntegradorSettingsService;
use MelhorEnvio\Services\Shipping\CartItemsBuilderService;
use MelhorEnvio\Tests\TestCase;
use Mockery;

require_once dirname( __DIR__, 4 ) . '/Stubs/Extra/QuotationControllerTest.php';

/**
 * All collaborators are final, so the real services are used and the WordPress functions
 * they call (get_option, wp_remote_post, wp_remote_get...) are faked instead.
 */
final class QuotationControllerTest extends TestCase {

	private const HALT       = 'wp_send_json halted';
	private const STORE_CEP  = '01310100';
	private const DEST_CEP   = '20040002';
	private const PRODUCT_ID = 42;

	private QuotationController $controller;

	/** @var array<string, mixed> */
	private array $options = array();

	/** @var array{success: bool, data: mixed}|null */
	private ?array $response = null;

	/** @var array<int, array<string, mixed>> Payloads posted to the Melhor Envio API. */
	private array $apiPayloads = array();

	/** @var array<int, array<string, mixed>> Quotations returned by the fake Melhor Envio API. */
	private array $apiQuotations = array();

	protected function setUp(): void {
		parent::setUp();

		Functions\stubTranslationFunctions();
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'wp_unslash' )->returnArg();
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'wc_get_dimension' )->returnArg();
		Functions\when( 'wc_get_weight' )->returnArg();
		Functions\when( 'wc_get_logger' )->justReturn( Mockery::mock( 'WC_Logger' )->shouldIgnoreMissing() );
		Functions\when( 'get_option' )->alias(
			function ( string $name, $default = false ) {
				return array_key_exists( $name, $this->options ) ? $this->options[ $name ] : $default;
			}
		);
		Functions\when( 'wp_send_json_error' )->alias(
			function ( $data = null ): void {
				$this->response = array(
					'success' => false,
					'data'    => $data,
				);
				throw new \RuntimeException( self::HALT );
			}
		);
		Functions\when( 'wp_send_json_success' )->alias(
			function ( $data = null ): void {
				$this->response = array(
					'success' => true,
					'data'    => $data,
				);
				throw new \RuntimeException( self::HALT );
			}
		);
		Functions\when( 'wp_remote_get' )->justReturn(
			array(
				'response' => array( 'code' => 200 ),
				'body'     => json_encode( array( 'uf' => 'RJ' ) ),
			)
		);
		Functions\when( 'wp_remote_post' )->alias(
			function ( string $url, array $args ): array {
				$this->apiPayloads[] = json_decode( $args['body'], true );
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => json_encode( $this->apiQuotations ),
				);
			}
		);
		Functions\when( 'wp_remote_retrieve_response_code' )->alias(
			static function ( array $response ): int {
				return $response['response']['code'];
			}
		);
		Functions\when( 'wp_remote_retrieve_body' )->alias(
			static function ( array $response ): string {
				return $response['body'];
			}
		);

		$this->options = array(
			'woocommerce_store_postcode'               => '01310-100',
			'melhor_envio_integrador_quotation_token'  => 'token',
			'melhor_envio_integrador_settings'         => array(
				'dimensions_default' => array(
					'width'  => 11.0,
					'height' => 3.0,
					'length' => 16.0,
					'weight' => 0.3,
				),
			),
		);

		$this->controller = new QuotationController(
			new MelhorEnvioApiClientService(),
			new CartItemsBuilderService( new IntegradorSettingsService() ),
			new PostalCodeLocationClientService()
		);
	}

	public function test_register_hooks_ajax_action_for_guests_and_logged_in_users(): void {
		$this->controller->register();

		self::assertNotFalse( has_action( 'wp_ajax_me_quote', array( $this->controller, 'handle' ) ) );
		self::assertNotFalse( has_action( 'wp_ajax_nopriv_me_quote', array( $this->controller, 'handle' ) ) );
	}

	/*
	 * Guard clauses
	 */

	/**
	 * @dataProvider invalidCeps
	 */
	public function test_rejects_invalid_destination_cep( string $cep ): void {
		$_POST = array(
			'cep'        => $cep,
			'product_id' => (string) self::PRODUCT_ID,
		);
		Functions\expect( 'wc_get_product' )->never();

		$this->assertError( 'CEP inválido.', $this->dispatch() );
	}

	public function invalidCeps(): array {
		return array(
			'missing'   => array( '' ),
			'too short' => array( '2004-000' ),
			'too long'  => array( '20040-0020' ),
			'letters'   => array( 'abcdefgh' ),
		);
	}

	public function test_rejects_request_without_product(): void {
		$_POST = array( 'cep' => self::DEST_CEP );
		Functions\expect( 'wc_get_product' )->never();

		$this->assertError( 'Produto não encontrado.', $this->dispatch() );
	}

	public function test_rejects_unknown_product(): void {
		$this->givenRequest();
		Functions\expect( 'wc_get_product' )->once()->with( self::PRODUCT_ID )->andReturn( false );

		$this->assertError( 'Produto não encontrado.', $this->dispatch() );
	}

	public function test_rejects_when_store_postcode_is_not_configured(): void {
		$this->givenRequest();
		$this->givenProduct( $this->mockProduct() );
		$this->options['woocommerce_store_postcode'] = '';

		$this->assertError( 'CEP de origem não configurado.', $this->dispatch() );
	}

	public function test_rejects_when_no_rate_is_available(): void {
		$this->givenRequest();
		$this->givenProduct( $this->mockProduct() );
		$this->givenZoneMethods( array( $this->meMethod() ) );

		$this->assertError( 'Frete não disponível para este endereço.', $this->dispatch() );
	}

	/*
	 * Melhor Envio quotations
	 */

	public function test_quotes_simple_product_with_its_own_dimensions(): void {
		$this->givenRequest( array( 'quantity' => '3' ) );
		$this->givenProduct( $this->mockProduct( array(
			'width'  => '20',
			'height' => '10',
			'length' => '30',
			'weight' => '1.5',
			'price'  => '99.90',
		) ) );
		$this->givenZoneMethods( array( $this->meMethod() ) );
		$this->apiQuotations = array( $this->quotation( 'PAC', 25.5 ) );

		$this->dispatch();

		self::assertCount( 1, $this->apiPayloads );
		self::assertSame( array( 'postal_code' => self::STORE_CEP ), $this->apiPayloads[0]['from'] );
		self::assertSame( array( 'postal_code' => self::DEST_CEP ), $this->apiPayloads[0]['to'] );
		self::assertEquals(
			array(
				array(
					'id'              => self::PRODUCT_ID,
					'width'           => 20.0,
					'height'          => 10.0,
					'length'          => 30.0,
					'weight'          => 1.5,
					'insurance_value' => 99.9,
					'quantity'        => 3,
				),
			),
			$this->apiPayloads[0]['products']
		);
	}

	public function test_quotes_simple_product_with_default_dimensions_when_missing(): void {
		$this->givenRequest();
		$this->givenProduct( $this->mockProduct() );
		$this->givenZoneMethods( array( $this->meMethod() ) );
		$this->apiQuotations = array( $this->quotation( 'PAC', 25.5 ) );

		$this->dispatch();

		$item = $this->apiPayloads[0]['products'][0];
		self::assertEquals( 11.0, $item['width'] );
		self::assertEquals( 3.0, $item['height'] );
		self::assertEquals( 16.0, $item['length'] );
		self::assertEquals( 0.3, $item['weight'] );
	}

	public function test_quotes_simple_product_with_hardcoded_dimensions_without_settings(): void {
		unset( $this->options['melhor_envio_integrador_settings'] );
		$this->givenRequest();
		$this->givenProduct( $this->mockProduct() );
		$this->givenZoneMethods( array( $this->meMethod() ) );
		$this->apiQuotations = array( $this->quotation( 'PAC', 25.5 ) );

		$this->dispatch();

		$item = $this->apiPayloads[0]['products'][0];
		self::assertEquals( 12.0, $item['width'] );
		self::assertEquals( 2.0, $item['height'] );
		self::assertEquals( 17.0, $item['length'] );
		self::assertEquals( 0.5, $item['weight'] );
	}

	/**
	 * @dataProvider invalidQuantities
	 */
	public function test_quantity_is_at_least_one( string $quantity ): void {
		$this->givenRequest( array( 'quantity' => $quantity ) );
		$this->givenProduct( $this->mockProduct() );
		$this->givenZoneMethods( array( $this->meMethod() ) );
		$this->apiQuotations = array( $this->quotation( 'PAC', 25.5 ) );

		$this->dispatch();

		self::assertSame( 1, $this->apiPayloads[0]['products'][0]['quantity'] );
	}

	public function invalidQuantities(): array {
		return array(
			'zero'     => array( '0' ),
			'negative' => array( '-4' ),
			'garbage'  => array( 'abc' ),
		);
	}

	public function test_accepts_masked_destination_cep(): void {
		$this->givenRequest( array( 'cep' => '20040-002' ) );
		$this->givenProduct( $this->mockProduct() );
		$this->givenZoneMethods( array( $this->meMethod() ) );
		$this->apiQuotations = array( $this->quotation( 'PAC', 25.5 ) );

		$response = $this->dispatch();

		self::assertTrue( $response['success'] );
		self::assertSame( array( 'postal_code' => self::DEST_CEP ), $this->apiPayloads[0]['to'] );
	}

	public function test_returns_quotations_mapped_and_sorted_by_price(): void {
		$this->givenRequest();
		$this->givenProduct( $this->mockProduct() );
		$this->givenZoneMethods( array( $this->meMethod() ) );
		$this->apiQuotations = array(
			$this->quotation( 'SEDEX', 40.0, 2, 'Correios' ),
			array(
				'name'                 => '.Package',
				'company'              => array( 'name' => 'Jadlog' ),
				'price'                => '30.00',
				'custom_price'         => '18.75',
				'delivery_time'        => 7,
				'custom_delivery_time' => 9,
			),
		);

		$response = $this->dispatch();

		self::assertTrue( $response['success'] );
		self::assertSame(
			array(
				array(
					'name'          => '.Package',
					'company'       => 'Jadlog',
					'price'         => 18.75,
					'delivery_time' => 9,
				),
				array(
					'name'          => 'SEDEX',
					'company'       => 'Correios',
					'price'         => 40.0,
					'delivery_time' => 2,
				),
			),
			$response['data']
		);
	}

	public function test_does_not_call_melhor_envio_when_zone_has_no_melhor_envio_method(): void {
		$this->givenRequest();
		$this->givenProduct( $this->mockProduct() );
		$this->givenZoneMethods( array( $this->nativeMethod( 'flat_rate', 'Taxa fixa', array( 15.0 ) ) ) );

		$response = $this->dispatch();

		self::assertSame( array(), $this->apiPayloads );
		self::assertTrue( $response['success'] );
		self::assertSame( array( 'Taxa fixa' ), array_column( $response['data'], 'name' ) );
	}

	/*
	 * Composite / bundle products
	 */

	public function test_composite_product_without_selection_asks_customer_to_pick_items(): void {
		$this->givenRequest();
		$this->givenProduct( $this->mockProduct( array( 'type' => 'composite' ) ) );
		$this->givenZoneMethods( array( $this->meMethod() ) );

		$this->assertError( 'Selecione os itens da composição antes de calcular o frete.', $this->dispatch() );
		self::assertSame( array(), $this->apiPayloads );
	}

	public function test_bundle_with_optional_items_and_no_selection_is_quoted_only_in_cart(): void {
		$this->givenRequest();
		$product = $this->mockProduct( array( 'type' => 'woosb' ), 'WC_Product, QuotationControllerTestWoosbProduct' );
		$product->allows( 'has_optional' )->andReturn( true );
		$this->givenProduct( $product );
		$this->givenZoneMethods( array( $this->meMethod() ) );

		$this->assertError( 'Cotação deste produto disponível apenas no carrinho.', $this->dispatch() );
		self::assertSame( array(), $this->apiPayloads );
	}

	public function test_bundle_without_optional_items_is_quoted_from_default_composition(): void {
		$this->givenRequest();
		$this->givenProduct( $this->mockProduct( array( 'type' => 'woosb', 'price' => '50' ) ) );
		$this->givenZoneMethods( array( $this->meMethod() ) );
		$this->apiQuotations = array( $this->quotation( 'PAC', 25.5 ) );

		$response = $this->dispatch();

		self::assertTrue( $response['success'] );
		self::assertCount( 1, $this->apiPayloads );
		self::assertEquals( 50.0, $this->apiPayloads[0]['products'][0]['insurance_value'] );
	}

	public function test_bundle_with_selection_is_quoted_through_cart_items_builder(): void {
		$this->givenRequest( array( 'woosb_ids' => '7/1/2' ) );
		$product = $this->mockProduct( array( 'type' => 'woosb', 'price' => '50' ), 'WC_Product, QuotationControllerTestWoosbProduct' );
		$product->allows( 'has_optional' )->andReturn( true );
		$this->givenProduct( $product );
		$this->givenZoneMethods( array( $this->meMethod() ) );
		$this->apiQuotations = array( $this->quotation( 'PAC', 25.5 ) );

		$response = $this->dispatch();

		self::assertTrue( $response['success'] );
		self::assertCount( 1, $this->apiPayloads );
		self::assertSame( self::PRODUCT_ID, $this->apiPayloads[0]['products'][0]['id'] );
		self::assertEquals( 50.0, $this->apiPayloads[0]['products'][0]['insurance_value'] );
	}

	/*
	 * Native WooCommerce methods
	 */

	public function test_includes_native_rates_alongside_melhor_envio_quotations(): void {
		$this->givenRequest();
		$this->givenProduct( $this->mockProduct() );
		$this->givenZoneMethods(
			array(
				$this->meMethod(),
				$this->nativeMethod( 'flat_rate', 'Taxa fixa', array( 30.0, 12.5 ) ),
				$this->nativeMethod( 'local_pickup', 'Retirada', array( 0.0 ) ),
			)
		);
		$this->apiQuotations = array( $this->quotation( 'PAC', 20.0 ) );

		$response = $this->dispatch();

		self::assertSame(
			array(
				array(
					'name'          => 'Retirada',
					'company'       => '',
					'price'         => 0.0,
					'delivery_time' => 0,
				),
				array(
					'name'          => 'Taxa fixa',
					'company'       => '',
					'price'         => 12.5,
					'delivery_time' => 0,
				),
				array(
					'name'          => 'PAC',
					'company'       => 'Correios',
					'price'         => 20.0,
					'delivery_time' => 5,
				),
			),
			$response['data']
		);
	}

	public function test_native_methods_receive_package_for_destination(): void {
		$this->givenRequest( array( 'quantity' => '2' ) );
		$product = $this->mockProduct( array( 'price' => '10' ) );
		$this->givenProduct( $product );
		$method = $this->nativeMethod( 'flat_rate', 'Taxa fixa', array( 15.0 ) );
		$this->givenZoneMethods( array( $method ) );

		$this->dispatch();

		self::assertCount( 1, $method->packages );
		$package = $method->packages[0];
		self::assertSame(
			array(
				'country'  => 'BR',
				'state'    => 'RJ',
				'postcode' => self::DEST_CEP,
			),
			$package['destination']
		);
		self::assertSame( $product, $package['contents'][ self::PRODUCT_ID ]['data'] );
		self::assertSame( 2, $package['contents'][ self::PRODUCT_ID ]['quantity'] );
		self::assertSame( 20.0, $package['contents_cost'] );
	}

	public function test_native_method_without_rates_is_skipped(): void {
		$this->givenRequest();
		$this->givenProduct( $this->mockProduct() );
		$this->givenZoneMethods(
			array(
				$this->nativeMethod( 'flat_rate', 'Sem taxa', array() ),
				$this->nativeMethod( 'flat_rate', 'Taxa fixa', array( 15.0 ) ),
			)
		);

		$response = $this->dispatch();

		self::assertSame( array( 'Taxa fixa' ), array_column( $response['data'], 'name' ) );
	}

	/**
	 * @dataProvider shippingClassScenarios
	 */
	public function test_native_method_restricted_to_shipping_class( int $productClass, int $methodClass, bool $shown ): void {
		$this->givenRequest();
		$this->givenProduct( $this->mockProduct( array( 'shipping_class_id' => $productClass ) ) );
		$method = $this->nativeMethod( 'flat_rate', 'Taxa fixa', array( 15.0 ) );
		$method->instance_settings['shipping_class_id'] = (string) $methodClass;
		$this->givenZoneMethods( array( $method ) );

		$response = $this->dispatch();

		if ( $shown ) {
			self::assertSame( array( 'Taxa fixa' ), array_column( $response['data'], 'name' ) );
		} else {
			$this->assertError( 'Frete não disponível para este endereço.', $response );
		}
	}

	public function shippingClassScenarios(): array {
		return array(
			'same class'                     => array( 5, 5, true ),
			'different class'                => array( 5, 6, false ),
			'method without class'           => array( 5, 0, true ),
			'product without class'          => array( 0, 6, true ),
		);
	}

	/**
	 * @dataProvider freeShippingObservations
	 */
	public function test_free_shipping_is_listed_with_its_condition( string $requires, string $minAmount, string $observation ): void {
		$this->givenRequest();
		$this->givenProduct( $this->mockProduct() );
		$this->givenZoneMethods( array( $this->freeShippingMethod( $requires, $minAmount ) ) );

		$response = $this->dispatch();

		self::assertSame(
			array(
				array(
					'name'          => 'Frete grátis',
					'company'       => '',
					'price'         => 0.0,
					'delivery_time' => 0,
					'observation'   => $observation,
				),
			),
			$response['data']
		);
	}

	public function freeShippingObservations(): array {
		return array(
			'no requirement'          => array( '', '', '' ),
			'min amount'              => array( 'min_amount', '1500.5', 'Frete grátis para pedidos com valor mínimo de R$1.500,50.' ),
			'min amount zero'         => array( 'min_amount', '0', '' ),
			'min amount or coupon'    => array( 'either', '100', 'Frete grátis para pedidos com valor mínimo de R$100,00 ou uso de cupom.' ),
			'coupon (either, no min)' => array( 'either', '', 'Frete grátis mediante uso de cupom.' ),
			'min amount and coupon'   => array( 'both', '200', 'Frete grátis para pedidos com valor mínimo de R$200,00 e uso de cupom.' ),
		);
	}

	public function test_coupon_only_free_shipping_is_hidden(): void {
		$this->givenRequest();
		$this->givenProduct( $this->mockProduct() );
		$this->givenZoneMethods( array( $this->freeShippingMethod( 'coupon', '' ) ) );

		$this->assertError( 'Frete não disponível para este endereço.', $this->dispatch() );
	}

	/*
	 * Helpers
	 */

	/**
	 * @return array{success: bool, data: mixed}|null
	 */
	private function dispatch(): ?array {
		// Every request must be nonce-checked before anything else happens.
		Functions\expect( 'check_ajax_referer' )->once()->with( 'me_quote', 'nonce' )->andReturn( 1 );

		try {
			$this->controller->handle();
		} catch ( \RuntimeException $e ) {
			if ( $e->getMessage() !== self::HALT ) {
				throw $e;
			}
		}

		return $this->response;
	}

	/**
	 * @param array{success: bool, data: mixed}|null $response
	 */
	private function assertError( string $message, ?array $response ): void {
		self::assertNotNull( $response );
		self::assertFalse( $response['success'] );
		self::assertSame( array( 'message' => $message ), $response['data'] );
	}

	private function givenRequest( array $overrides = array() ): void {
		$_POST = array_merge(
			array(
				'cep'        => self::DEST_CEP,
				'product_id' => (string) self::PRODUCT_ID,
				'quantity'   => '1',
			),
			$overrides
		);
	}

	/**
	 * @param \Mockery\MockInterface $product
	 */
	private function givenProduct( $product ): void {
		Functions\when( 'wc_get_product' )->justReturn( $product );
	}

	private function givenZoneMethods( array $methods ): void {
		$zone          = new \WC_Shipping_Zone( 1 );
		$zone->methods = $methods;

		\WC_Shipping_Zones::$matching_zone = $zone;
	}

	/**
	 * @param array<string, mixed> $props
	 * @return \Mockery\MockInterface&\WC_Product
	 */
	private function mockProduct( array $props = array(), string $type = 'WC_Product' ) {
		$props = array_merge(
			array(
				'type'              => 'simple',
				'width'             => '',
				'height'            => '',
				'length'            => '',
				'weight'            => '',
				'price'             => '10',
				'shipping_class_id' => 0,
			),
			$props
		);

		$product = Mockery::mock( $type );
		$product->allows( 'get_id' )->andReturn( self::PRODUCT_ID );

		foreach ( $props as $prop => $value ) {
			$product->allows( 'get_' . $prop )->andReturn( $value );
		}

		return $product;
	}

	private function meMethod(): object {
		return (object) array( 'id' => 'melhor_envio' );
	}

	/**
	 * @param float[] $costs Costs of the rates returned by the method (the last one is used).
	 */
	private function nativeMethod( string $id, string $title, array $costs ): \WC_Shipping_Method {
		$method = new class() extends \WC_Shipping_Method {

			/** @var float[] */
			public $costs = array();

			/** @var array<int, array<string, mixed>> */
			public $packages = array();

			public function get_rates_for_package( $package ): array {
				$this->packages[] = $package;

				return array_map(
					static function ( float $cost ) {
						$rate = Mockery::mock( 'WC_Shipping_Rate' );
						$rate->allows( 'get_cost' )->andReturn( (string) $cost );
						return $rate;
					},
					$this->costs
				);
			}
		};

		$method->id    = $id;
		$method->title = $title;
		$method->costs = $costs;

		return $method;
	}

	private function freeShippingMethod( string $requires, string $minAmount ): \WC_Shipping_Method {
		$method = new class() extends \WC_Shipping_Method {

			/** @var string */
			public $requires = '';

			/** @var string */
			public $min_amount = '';
		};

		$method->id         = 'free_shipping';
		$method->title      = 'Frete grátis';
		$method->requires   = $requires;
		$method->min_amount = $minAmount;

		return $method;
	}

	private function quotation( string $name, float $price, int $deliveryTime = 5, string $company = 'Correios' ): array {
		return array(
			'name'          => $name,
			'company'       => array( 'name' => $company ),
			'price'         => (string) $price,
			'delivery_time' => $deliveryTime,
		);
	}
}
