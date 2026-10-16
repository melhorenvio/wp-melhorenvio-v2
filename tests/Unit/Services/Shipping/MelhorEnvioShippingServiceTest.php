<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Services\Shipping;

use Brain\Monkey\Functions;
use MelhorEnvio\Services\Shipping\MelhorEnvioShippingService;
use MelhorEnvio\Tests\TestCase;
use Mockery;

/**
 * MelhorEnvioShippingService builds its collaborators with `new` and both are final classes
 * (MelhorEnvioApiClientService, CartItemsBuilderService), so they can't be replaced by Mockery
 * doubles. The real collaborators are exercised instead, with their WordPress/WooCommerce edges
 * (WC()->cart, get_option, wp_remote_post...) mocked through Brain Monkey.
 */
final class MelhorEnvioShippingServiceTest extends TestCase {

	private const FROM_CEP = '01310100';
	private const TO_CEP   = '20040020';

	/** @var array<string, mixed> */
	private array $options = array();

	/** @var \Mockery\MockInterface */
	private $logger;

	protected function setUp(): void {
		parent::setUp();

		Functions\stubTranslationFunctions();

		$this->options = array(
			'woocommerce_store_postcode'              => '01310-100',
			'woocommerce_dimension_unit'              => 'cm',
			'woocommerce_weight_unit'                 => 'kg',
			'melhor_envio_integrador_quotation_token' => 'token-123',
		);

		Functions\when( 'get_option' )->alias(
			function ( $key, $default = false ) {
				return array_key_exists( $key, $this->options ) ? $this->options[ $key ] : $default;
			}
		);
		Functions\when( 'wc_get_dimension' )->returnArg( 1 );
		Functions\when( 'wc_get_weight' )->returnArg( 1 );

		$this->logger = Mockery::mock( 'WC_Logger' );
		$this->logger->allows( 'debug' );
		Functions\when( 'wc_get_logger' )->justReturn( $this->logger );

		$this->givenCart( array() );
	}

	public function test_constructor_configures_shipping_method(): void {
		$method = new MelhorEnvioShippingService( 7 );

		self::assertSame( 'melhor_envio', $method->id );
		self::assertSame( 7, $method->instance_id );
		self::assertSame( 'Melhor Envio', $method->method_title );
		self::assertSame( 'Cotação automática via Melhor Envio.', $method->method_description );
		self::assertSame( array( 'shipping-zones', 'instance-settings' ), $method->supports );
		self::assertSame( 'yes', $method->enabled );
		self::assertSame( 'Melhor Envio', $method->title );
	}

	public function test_constructor_normalizes_negative_instance_id(): void {
		$method = new MelhorEnvioShippingService( -4 );

		self::assertSame( 4, $method->instance_id );
	}

	public function test_title_comes_from_instance_settings(): void {
		\WC_Shipping_Method::$seed_settings = array( 'title' => 'Frete expresso' );

		$method = new MelhorEnvioShippingService( 1 );

		self::assertSame( 'Frete expresso', $method->title );
	}

	public function test_declares_title_form_field(): void {
		$method = new MelhorEnvioShippingService();

		self::assertSame(
			array(
				'title' => array(
					'title'   => 'Título',
					'type'    => 'text',
					'default' => 'Melhor Envio',
				),
			),
			$method->form_fields
		);
	}

	public function test_registers_admin_options_save_hook(): void {
		$method = new MelhorEnvioShippingService();

		self::assertNotFalse(
			has_action( 'woocommerce_update_options_shipping_melhor_envio', array( $method, 'process_admin_options' ) )
		);
	}

	/**
	 * @dataProvider missingPostcodes
	 *
	 * @param array<string, mixed> $package
	 */
	public function test_calculate_shipping_aborts_without_postcodes( string $storePostcode, array $package ): void {
		$this->options['woocommerce_store_postcode'] = $storePostcode;

		$this->logger->expects( 'warning' )->with(
			Mockery::pattern( '/^calculate_shipping abortado: CEP ausente\./' ),
			array( 'source' => 'melhor-envio-cotacao' )
		);
		Functions\expect( 'get_transient' )->never();
		Functions\expect( 'wp_remote_post' )->never();

		$method = new MelhorEnvioShippingService();
		$method->calculate_shipping( $package );

		self::assertSame( array(), $method->rates );
	}

	public function missingPostcodes(): array {
		return array(
			'no store postcode'          => array( '', $this->package( self::TO_CEP ) ),
			'store postcode not digits'  => array( 'abc-def', $this->package( self::TO_CEP ) ),
			'no destination postcode'    => array( self::FROM_CEP, $this->package( '' ) ),
			'destination without digits' => array( self::FROM_CEP, $this->package( '-' ) ),
			'destination key missing'    => array( self::FROM_CEP, array( 'destination' => array() ) ),
			'empty package'              => array( self::FROM_CEP, array() ),
		);
	}

	public function test_calculate_shipping_uses_cached_quotations(): void {
		Functions\expect( 'get_transient' )
			->once()
			->with( 'me_quote_' . md5( self::TO_CEP . serialize( array() ) ) )
			->andReturn( array( $this->quotation() ) );
		Functions\expect( 'wp_remote_post' )->never();
		Functions\expect( 'set_transient' )->never();

		$method = new MelhorEnvioShippingService();
		$method->calculate_shipping( $this->package( '20040-020' ) );

		self::assertCount( 1, $method->rates );
	}

	public function test_calculate_shipping_cache_key_depends_on_cart_items(): void {
		$product = $this->mockProduct( 3, '25' );
		$this->givenCart(
			array(
				'k' => array(
					'data'     => $product,
					'quantity' => 2,
				),
			)
		);

		$expectedItems = array(
			array(
				'id'              => 3,
				'width'           => 11.0,
				'height'          => 3.0,
				'length'          => 16.0,
				'weight'          => 0.3,
				'insurance_value' => 25.0,
				'quantity'        => 2,
			),
		);

		Functions\expect( 'get_transient' )
			->once()
			->with( 'me_quote_' . md5( self::TO_CEP . serialize( $expectedItems ) ) )
			->andReturn( array() );

		$method = new MelhorEnvioShippingService();
		$method->calculate_shipping( $this->package( self::TO_CEP ) );

		self::assertSame( array(), $method->rates );
	}

	public function test_calculate_shipping_requests_and_caches_quotations_on_cache_miss(): void {
		$this->givenCart(
			array(
				'k' => array(
					'data'     => $this->mockProduct( 3, '25' ),
					'quantity' => 1,
				),
			)
		);

		$cacheKey = null;
		Functions\when( 'get_transient' )->alias(
			static function ( $key ) use ( &$cacheKey ) {
				$cacheKey = $key;
				return false;
			}
		);

		Functions\expect( 'wp_remote_post' )
			->once()
			->with(
				'https://melhorenvio.com.br/api/v2/me/shipment/calculate',
				Mockery::on(
					static function ( array $args ): bool {
						$body = json_decode( $args['body'], true );

						return $args['headers']['Authorization'] === 'Bearer token-123'
							&& $body['from']['postal_code'] === self::FROM_CEP
							&& $body['to']['postal_code'] === self::TO_CEP
							&& $body['products'][0]['id'] === 3
							&& (float) $body['products'][0]['insurance_value'] === 25.0;
					}
				)
			)
			->andReturn( array( 'response' => array( 'code' => 200 ) ) );
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 200 );
		Functions\when( 'wp_remote_retrieve_body' )->justReturn(
			json_encode(
				array(
					$this->quotation(),
					array(
						'id'    => 2,
						'name'  => 'SEDEX',
						'error' => 'Serviço indisponível',
					),
				)
			)
		);

		Functions\expect( 'set_transient' )
			->once()
			->with(
				Mockery::on(
					static function ( $key ) use ( &$cacheKey ): bool {
						return $key === $cacheKey;
					}
				),
				array( 0 => $this->quotation() ),
				30 * MINUTE_IN_SECONDS
			);

		$method = new MelhorEnvioShippingService();
		$method->calculate_shipping( $this->package( self::TO_CEP ) );

		self::assertSame( array( 'melhor_envio_1' ), array_column( $method->rates, 'id' ) );
	}

	public function test_calculate_shipping_adds_rate_with_service_metadata(): void {
		$method = $this->calculateWithCachedQuotations( array( $this->quotation() ) );

		self::assertSame(
			array(
				array(
					'id'        => 'melhor_envio_1',
					'label'     => 'Correios - PAC (5 dias úteis)',
					'cost'      => 23.5,
					'meta_data' => array(
						'me_service_id'    => '1',
						'me_service_name'  => 'PAC',
						'me_company_id'    => '1',
						'me_company_name'  => 'Correios',
						'me_delivery_time' => '5',
					),
				),
			),
			$method->rates
		);
	}

	public function test_calculate_shipping_prefers_custom_price_and_delivery_time(): void {
		$method = $this->calculateWithCachedQuotations(
			array(
				$this->quotation(
					array(
						'custom_price'         => '19.9',
						'custom_delivery_time' => 8,
					)
				),
			)
		);

		self::assertSame( 19.9, $method->rates[0]['cost'] );
		self::assertSame( 'Correios - PAC (8 dias úteis)', $method->rates[0]['label'] );
		self::assertSame( '8', $method->rates[0]['meta_data']['me_delivery_time'] );
	}

	public function test_calculate_shipping_falls_back_when_custom_values_are_null(): void {
		$method = $this->calculateWithCachedQuotations(
			array(
				$this->quotation(
					array(
						'custom_price'         => null,
						'custom_delivery_time' => null,
					)
				),
			)
		);

		self::assertSame( 23.5, $method->rates[0]['cost'] );
		self::assertSame( 'Correios - PAC (5 dias úteis)', $method->rates[0]['label'] );
	}

	public function test_calculate_shipping_label_without_company_or_delivery_time(): void {
		$method = $this->calculateWithCachedQuotations(
			array(
				array(
					'id'    => 9,
					'name'  => 'Retirada',
					'price' => 0,
				),
			)
		);

		self::assertSame( 'Retirada', $method->rates[0]['label'] );
		self::assertSame( 0.0, $method->rates[0]['cost'] );
		self::assertSame(
			array(
				'me_service_id'    => '9',
				'me_service_name'  => 'Retirada',
				'me_company_id'    => '0',
				'me_company_name'  => '',
				'me_delivery_time' => '0',
			),
			$method->rates[0]['meta_data']
		);
	}

	public function test_calculate_shipping_handles_service_without_id_name_or_price(): void {
		$method = $this->calculateWithCachedQuotations( array( array() ) );

		self::assertMatchesRegularExpression( '/^melhor_envio_[0-9a-f]+$/', $method->rates[0]['id'] );
		self::assertSame( 'Melhor Envio', $method->rates[0]['label'] );
		self::assertSame( 0.0, $method->rates[0]['cost'] );
		self::assertSame( '', $method->rates[0]['meta_data']['me_service_id'] );
	}

	public function test_calculate_shipping_adds_one_rate_per_service(): void {
		$method = $this->calculateWithCachedQuotations(
			array(
				$this->quotation(),
				$this->quotation(
					array(
						'id'      => 2,
						'name'    => '.Package',
						'company' => array(
							'id'   => 2,
							'name' => 'Jadlog',
						),
					)
				),
			)
		);

		self::assertSame( array( 'melhor_envio_1', 'melhor_envio_2' ), array_column( $method->rates, 'id' ) );
		self::assertSame( 'Jadlog - .Package (5 dias úteis)', $method->rates[1]['label'] );
	}

	public function test_calculate_shipping_adds_no_rate_without_quotations(): void {
		$method = $this->calculateWithCachedQuotations( array() );

		self::assertSame( array(), $method->rates );
	}

	/**
	 * @param array<int, array<string, mixed>> $quotations
	 */
	private function calculateWithCachedQuotations( array $quotations ): MelhorEnvioShippingService {
		Functions\when( 'get_transient' )->justReturn( $quotations );
		Functions\expect( 'wp_remote_post' )->never();

		$method = new MelhorEnvioShippingService();
		$method->calculate_shipping( $this->package( self::TO_CEP ) );

		return $method;
	}

	/**
	 * @param array<string, mixed> $overrides
	 */
	private function quotation( array $overrides = array() ): array {
		return array_merge(
			array(
				'id'            => 1,
				'name'          => 'PAC',
				'price'         => '23.50',
				'delivery_time' => 5,
				'company'       => array(
					'id'   => 1,
					'name' => 'Correios',
				),
			),
			$overrides
		);
	}

	private function package( string $postcode ): array {
		return array( 'destination' => array( 'postcode' => $postcode ) );
	}

	/**
	 * @param array<string, mixed> $cartItems
	 */
	private function givenCart( array $cartItems ): void {
		$cart = Mockery::mock( 'WC_Cart' );
		$cart->allows( 'get_cart' )->andReturn( $cartItems );

		Functions\when( 'WC' )->justReturn( (object) array( 'cart' => $cart ) );
	}

	/**
	 * @return \Mockery\MockInterface&\WC_Product
	 */
	private function mockProduct( int $id, string $price ) {
		$product = Mockery::mock( 'WC_Product' );
		$product->allows(
			array(
				'get_id'     => $id,
				'get_type'   => 'simple',
				'get_price'  => $price,
				'get_width'  => '11',
				'get_height' => '3',
				'get_length' => '16',
				'get_weight' => '0.3',
			)
		);

		return $product;
	}
}
