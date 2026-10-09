<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Services\Shipping;

use Brain\Monkey\Functions;
use MelhorEnvio\Services\Settings\IntegradorSettingsService;
use MelhorEnvio\Services\Shipping\CartItemsBuilderService;
use MelhorEnvio\Tests\TestCase;
use Mockery;

require_once dirname( __DIR__, 3 ) . '/Stubs/Extra/CartItemsBuilderServiceTest.php';

final class CartItemsBuilderServiceTest extends TestCase {

	private const WOOSB = 'CartItemsBuilderServiceTestWoosbProduct';
	private const WOOCO = 'CartItemsBuilderServiceTestWoocoProduct';

	private const DEFAULT_DIMENSIONS = array(
		'width'  => '11',
		'height' => '3',
		'length' => '16',
		'weight' => '0.3',
	);

	private CartItemsBuilderService $service;

	/** @var array<string, mixed> */
	private array $options = array();

	/** @var array<int, array<string, string>> */
	private array $postMeta = array();

	/** @var array<int, object> */
	private array $catalog = array();

	protected function setUp(): void {
		parent::setUp();

		$this->options  = array(
			'woocommerce_dimension_unit' => 'cm',
			'woocommerce_weight_unit'    => 'kg',
		);
		$this->postMeta = array();
		$this->catalog  = array();

		\WPCleverWooco_Helper::$items     = array();
		\WPCleverWooco_Helper::$requested = array();

		Functions\when( 'get_option' )->alias(
			function ( $key, $default = false ) {
				return array_key_exists( $key, $this->options ) ? $this->options[ $key ] : $default;
			}
		);
		Functions\when( 'get_post_meta' )->alias(
			function ( $postId, $key = '', $single = false ) {
				return $this->postMeta[ $postId ][ $key ] ?? '';
			}
		);
		Functions\when( 'wc_get_product' )->alias(
			function ( $id ) {
				return $this->catalog[ $id ] ?? false;
			}
		);
		Functions\when( 'wc_get_dimension' )->alias(
			static function ( $value, $toUnit, $fromUnit = '' ) {
				$toCm = array(
					'cm' => 1.0,
					'mm' => 0.1,
					'm'  => 100.0,
					'in' => 2.54,
				);

				return $value * $toCm[ $fromUnit ];
			}
		);
		Functions\when( 'wc_get_weight' )->alias(
			static function ( $value, $toUnit, $fromUnit = '' ) {
				$toKg = array(
					'kg'  => 1.0,
					'g'   => 0.001,
					'lbs' => 0.5,
				);

				return $value * $toKg[ $fromUnit ];
			}
		);

		$this->service = new CartItemsBuilderService( new IntegradorSettingsService() );
	}

	protected function tearDown(): void {
		\WPCleverWooco_Helper::$items     = array();
		\WPCleverWooco_Helper::$requested = array();
		parent::tearDown();
	}

	// ---------------------------------------------------------------------
	// Product type detection.
	// ---------------------------------------------------------------------

	/**
	 * @dataProvider productTypes
	 */
	public function test_detects_product_type( string $type, bool $isBundle, bool $isComposite ): void {
		$product = $this->mockProduct( 1, '10', array(), $type );

		self::assertSame( $isBundle, CartItemsBuilderService::isBundleProduct( $product ) );
		self::assertSame( $isComposite, CartItemsBuilderService::isCompositeProduct( $product ) );
		self::assertSame( $isBundle || $isComposite, CartItemsBuilderService::isComposedProduct( $product ) );
	}

	public function productTypes(): array {
		return array(
			'woosb'             => array( 'woosb', true, false ),
			'product-woosb'     => array( 'product-woosb', true, false ),
			'composite'         => array( 'composite', false, true ),
			'product-composite' => array( 'product-composite', false, true ),
			'simple'            => array( 'simple', false, false ),
			'variable'          => array( 'variable', false, false ),
			'bundle (other)'    => array( 'bundle', false, false ),
		);
	}

	public function test_bundle_has_optional_items_is_false_when_product_lacks_method(): void {
		self::assertFalse( CartItemsBuilderService::bundleHasOptionalItems( $this->mockProduct( 1 ) ) );
	}

	/**
	 * @dataProvider booleans
	 */
	public function test_bundle_has_optional_items_reflects_product( bool $hasOptional ): void {
		$product = $this->mockProduct( 1, '10', array(), 'woosb', self::WOOSB );
		$product->allows( 'has_optional' )->andReturn( $hasOptional );

		self::assertSame( $hasOptional, CartItemsBuilderService::bundleHasOptionalItems( $product ) );
	}

	public function booleans(): array {
		return array(
			'true'  => array( true ),
			'false' => array( false ),
		);
	}

	// ---------------------------------------------------------------------
	// toLine / toApiItem.
	// ---------------------------------------------------------------------

	public function test_to_line_builds_line_array(): void {
		$product = $this->mockProduct( 1 );

		self::assertSame(
			array(
				'product'      => $product,
				'quantity'     => 3,
				'unitaryValue' => 9.9,
			),
			$this->service->toLine( $product, 3, 9.9 )
		);
	}

	public function test_to_api_item_uses_product_dimensions(): void {
		$product = $this->mockProduct( 5, '10', array( 'width' => '20', 'height' => '5.5', 'length' => '30', 'weight' => '1.25' ) );

		self::assertSame(
			array(
				'id'              => 5,
				'width'           => 20.0,
				'height'          => 5.5,
				'length'          => 30.0,
				'weight'          => 1.25,
				'insurance_value' => 49.9,
				'quantity'        => 2,
			),
			$this->service->toApiItem( $this->service->toLine( $product, 2, 49.9 ) )
		);
	}

	public function test_to_api_item_converts_store_units_to_cm_and_kg(): void {
		$this->options['woocommerce_dimension_unit'] = 'MM';
		$this->options['woocommerce_weight_unit']    = 'g';

		$product = $this->mockProduct( 5, '10', array( 'width' => '200', 'height' => '50', 'length' => '300', 'weight' => '1500' ) );
		$item    = $this->service->toApiItem( $this->service->toLine( $product, 1, 10.0 ) );

		self::assertEqualsWithDelta( 20.0, $item['width'], 0.0001 );
		self::assertEqualsWithDelta( 5.0, $item['height'], 0.0001 );
		self::assertEqualsWithDelta( 30.0, $item['length'], 0.0001 );
		self::assertEqualsWithDelta( 1.5, $item['weight'], 0.0001 );
	}

	/**
	 * @dataProvider emptyDimensionValues
	 *
	 * @param mixed $empty
	 */
	public function test_to_api_item_falls_back_to_hardcoded_defaults( $empty ): void {
		$product = $this->mockProduct(
			5,
			'10',
			array( 'width' => $empty, 'height' => $empty, 'length' => $empty, 'weight' => $empty )
		);
		$item    = $this->service->toApiItem( $this->service->toLine( $product, 1, 10.0 ) );

		self::assertSame( array( 12.0, 2.0, 17.0, 0.5 ), array( $item['width'], $item['height'], $item['length'], $item['weight'] ) );
	}

	public function emptyDimensionValues(): array {
		return array(
			'empty string' => array( '' ),
			'zero string'  => array( '0' ),
			'null'         => array( null ),
		);
	}

	public function test_to_api_item_falls_back_to_configured_default_dimensions(): void {
		$this->options['melhor_envio_integrador_settings'] = array(
			'dimensions_default' => array(
				'width'  => 10,
				'height' => 4,
				'length' => 15,
				'weight' => 0.8,
			),
		);

		$product = $this->mockProduct( 5, '10', array( 'width' => '', 'height' => '', 'length' => '', 'weight' => '' ) );
		$item    = $this->service->toApiItem( $this->service->toLine( $product, 1, 10.0 ) );

		self::assertSame( array( 10.0, 4.0, 15.0, 0.8 ), array( $item['width'], $item['height'], $item['length'], $item['weight'] ) );
	}

	public function test_to_api_item_mixes_product_configured_and_hardcoded_dimensions(): void {
		$this->options['melhor_envio_integrador_settings'] = array(
			'dimensions_default' => array( 'height' => 9 ),
		);

		$product = $this->mockProduct( 5, '10', array( 'width' => '25', 'height' => '', 'length' => '', 'weight' => '' ) );
		$item    = $this->service->toApiItem( $this->service->toLine( $product, 1, 10.0 ) );

		self::assertSame( array( 25.0, 9.0, 17.0, 0.5 ), array( $item['width'], $item['height'], $item['length'], $item['weight'] ) );
	}

	public function test_to_api_item_uses_legacy_default_dimensions_when_settings_not_migrated(): void {
		$this->options['melhor_envio_option_dimension_default'] = array(
			'width'  => '13',
			'height' => '6',
			'length' => '19',
			'weight' => '2',
		);

		$product = $this->mockProduct( 5, '10', array( 'width' => '', 'height' => '', 'length' => '', 'weight' => '' ) );
		$item    = $this->service->toApiItem( $this->service->toLine( $product, 1, 10.0 ) );

		self::assertSame( array( 13.0, 6.0, 19.0, 2.0 ), array( $item['width'], $item['height'], $item['length'], $item['weight'] ) );
	}

	public function test_to_api_item_hardcoded_defaults_are_not_converted_from_store_units(): void {
		$this->markTestIncomplete(
			'BUG: os fallbacks fixos (12x2x17 cm, 0,5 kg) passam pelo UnitConverter; em lojas g/mm a caixa vira 0,0005 kg. '
			. 'Correção: fix/default-dimensions-fallback-units.'
		);

		$this->options['woocommerce_dimension_unit'] = 'mm';
		$this->options['woocommerce_weight_unit']    = 'g';

		$product = $this->mockProduct( 5, '10', array( 'width' => '', 'height' => '', 'length' => '', 'weight' => '' ) );
		$item    = $this->service->toApiItem( $this->service->toLine( $product, 1, 10.0 ) );

		self::assertEqualsWithDelta( array( 12.0, 2.0, 17.0, 0.5 ), array( $item['width'], $item['height'], $item['length'], $item['weight'] ), 0.0001 );
	}

	/**
	 * Configured defaults are typed in the store unit (legacy ProductsService applied them as product
	 * dimensions before converting), so unlike the hardcoded fallbacks they must be converted.
	 */
	public function test_to_api_item_converts_configured_defaults_from_store_units(): void {
		$this->options['woocommerce_dimension_unit']       = 'mm';
		$this->options['woocommerce_weight_unit']          = 'g';
		$this->options['melhor_envio_integrador_settings'] = array(
			'dimensions_default' => array(
				'width'  => 100,
				'height' => 40,
				'length' => 150,
				'weight' => 800,
			),
		);

		$product = $this->mockProduct( 5, '10', array( 'width' => '', 'height' => '', 'length' => '', 'weight' => '' ) );
		$item    = $this->service->toApiItem( $this->service->toLine( $product, 1, 10.0 ) );

		self::assertEqualsWithDelta( array( 10.0, 4.0, 15.0, 0.8 ), array( $item['width'], $item['height'], $item['length'], $item['weight'] ), 0.0001 );
	}

	// ---------------------------------------------------------------------
	// composedItems.
	// ---------------------------------------------------------------------

	public function test_composed_items_quotes_components_when_shipping_fee_is_each(): void {
		$parent = $this->mockProduct( 1 );
		$a      = $this->mockProduct( 2 );
		$b      = $this->mockProduct( 3 );

		$items = $this->service->composedItems(
			$parent,
			1,
			100.0,
			'each',
			array( $this->service->toLine( $a, 2, 15.0 ), $this->service->toLine( $b, 1, 20.0 ) ),
			false
		);

		self::assertEquals( array( $this->apiItem( 2, 15.0, 2 ), $this->apiItem( 3, 20.0, 1 ) ), $items );
	}

	/**
	 * @dataProvider nonEachShippingFees
	 */
	public function test_composed_items_quotes_parent_for_other_shipping_fees( string $shippingFee ): void {
		$parent = $this->mockProduct( 1 );

		$items = $this->service->composedItems(
			$parent,
			4,
			100.0,
			$shippingFee,
			array( $this->service->toLine( $this->mockProduct( 2 ), 4, 15.0 ) ),
			false
		);

		self::assertEquals( array( $this->apiItem( 1, 25.0, 4 ) ), $items );
	}

	public function nonEachShippingFees(): array {
		return array(
			'whole' => array( 'whole' ),
			'empty' => array( '' ),
			'EACH'  => array( 'EACH' ),
		);
	}

	public function test_composed_items_quotes_parent_when_each_has_no_components(): void {
		$items = $this->service->composedItems( $this->mockProduct( 1 ), 2, 30.0, 'each', array(), true );

		self::assertEquals( array( $this->apiItem( 1, 15.0, 2 ) ), $items );
	}

	public function test_composed_items_guards_against_zero_quantity(): void {
		$items = $this->service->composedItems( $this->mockProduct( 1 ), 0, 30.0, '', array(), false );

		self::assertEquals( array( $this->apiItem( 1, 30.0, 0 ) ), $items );
	}

	public function test_composed_items_dumps_aggregate_into_first_component(): void {
		$items = $this->service->composedItems(
			$this->mockProduct( 1 ),
			1,
			90.0,
			'each',
			array(
				$this->service->toLine( $this->mockProduct( 2 ), 3, 15.0 ),
				$this->service->toLine( $this->mockProduct( 3 ), 1, 20.0 ),
				$this->service->toLine( $this->mockProduct( 4 ), 2, 5.0 ),
			),
			true
		);

		self::assertEquals(
			array( $this->apiItem( 2, 30.0, 3 ), $this->apiItem( 3, 0.0, 1 ), $this->apiItem( 4, 0.0, 2 ) ),
			$items
		);
	}

	public function test_composed_items_dump_guards_against_zero_quantity_component(): void {
		$items = $this->service->composedItems(
			$this->mockProduct( 1 ),
			1,
			90.0,
			'each',
			array( $this->service->toLine( $this->mockProduct( 2 ), 0, 15.0 ) ),
			true
		);

		self::assertEquals( array( $this->apiItem( 2, 90.0, 0 ) ), $items );
	}

	public function test_composed_items_dump_does_not_affect_parent_quote(): void {
		$items = $this->service->composedItems(
			$this->mockProduct( 1 ),
			2,
			90.0,
			'',
			array( $this->service->toLine( $this->mockProduct( 2 ), 2, 15.0 ) ),
			true
		);

		self::assertEquals( array( $this->apiItem( 1, 45.0, 2 ) ), $items );
	}

	// ---------------------------------------------------------------------
	// buildItems (cart).
	// ---------------------------------------------------------------------

	public function test_build_items_returns_empty_array_for_empty_cart(): void {
		$this->givenCart( array() );

		self::assertSame( array(), $this->service->buildItems() );
	}

	public function test_build_items_maps_simple_products(): void {
		$this->givenCart(
			array(
				'k1' => $this->cartItem( $this->mockProduct( 1, '19.90' ), 2 ),
				'k2' => $this->cartItem( $this->mockProduct( 2, '5', array( 'width' => '40' ) ), '3' ),
			)
		);

		self::assertEquals(
			array(
				$this->apiItem( 1, 19.9, 2 ),
				array_merge( $this->apiItem( 2, 5.0, 3 ), array( 'width' => 40.0 ) ),
			),
			$this->service->buildItems()
		);
	}

	public function test_build_items_uses_current_price_not_line_total(): void {
		$this->givenCart(
			array(
				'k1' => $this->cartItem( $this->mockProduct( 1, '12.5' ), 2, array( 'line_total' => 999 ) ),
			)
		);

		self::assertEquals( array( $this->apiItem( 1, 12.5, 2 ) ), $this->service->buildItems() );
	}

	public function test_build_items_skips_rows_without_product(): void {
		$this->givenCart(
			array(
				'no-data'    => array( 'quantity' => 1 ),
				'not-object' => array(
					'data'     => 'product',
					'quantity' => 1,
				),
				'std-class'  => array(
					'data'     => new \stdClass(),
					'quantity' => 1,
				),
				'valid'      => $this->cartItem( $this->mockProduct( 9 ), 1 ),
			)
		);

		self::assertEquals( array( $this->apiItem( 9, 10.0, 1 ) ), $this->service->buildItems() );
	}

	/**
	 * @dataProvider childParentKeys
	 */
	public function test_build_items_skips_bundle_and_composite_children( string $parentKey ): void {
		$this->givenCart(
			array(
				'child' => $this->cartItem( $this->mockProduct( 2 ), 1, array( $parentKey => 1 ) ),
				'other' => $this->cartItem( $this->mockProduct( 3 ), 1 ),
			)
		);

		self::assertEquals( array( $this->apiItem( 3, 10.0, 1 ) ), $this->service->buildItems() );
	}

	public function childParentKeys(): array {
		return array(
			'bundle child'    => array( 'woosb_parent_id' ),
			'composite child' => array( 'wooco_parent_id' ),
		);
	}

	public function test_build_items_treats_bundle_without_keys_as_simple_product(): void {
		$this->givenCart(
			array(
				'kit' => $this->cartItem( $this->mockProduct( 1, '80', array(), 'woosb' ), 1 ),
			)
		);

		self::assertEquals( array( $this->apiItem( 1, 80.0, 1 ) ), $this->service->buildItems() );
	}

	public function test_build_items_treats_composite_without_keys_as_simple_product(): void {
		$this->givenCart(
			array(
				'comp' => $this->cartItem( $this->mockProduct( 1, '80', array(), 'composite' ), 1 ),
			)
		);

		self::assertEquals( array( $this->apiItem( 1, 80.0, 1 ) ), $this->service->buildItems() );
	}

	public function test_build_items_expands_bundle_components_when_shipping_fee_is_each(): void {
		$this->postMeta[1]['woosb_shipping_fee'] = 'each';

		$this->givenCart( $this->bundleCart( false, array( 'woosb_price' => '50' ) ) );

		self::assertEquals(
			array( $this->apiItem( 2, 15.0, 2 ), $this->apiItem( 3, 20.0, 1 ), $this->apiItem( 4, 10.0, 1 ) ),
			$this->service->buildItems()
		);
	}

	public function test_build_items_quotes_bundle_parent_with_kit_price(): void {
		$this->postMeta[1]['woosb_shipping_fee'] = 'whole';

		$this->givenCart( $this->bundleCart( false, array( 'woosb_price' => '50' ) ) );

		self::assertEquals(
			array( $this->apiItem( 1, 50.0, 1 ), $this->apiItem( 4, 10.0, 1 ) ),
			$this->service->buildItems()
		);
	}

	public function test_build_items_falls_back_to_bundle_line_total_without_kit_price(): void {
		$this->givenCart( $this->bundleCart( false, array( 'woosb_price' => '', 'line_total' => 72.5 ) ) );

		self::assertEquals(
			array( $this->apiItem( 1, 72.5, 1 ), $this->apiItem( 4, 10.0, 1 ) ),
			$this->service->buildItems()
		);
	}

	public function test_build_items_dumps_fixed_price_bundle_value_into_first_component(): void {
		$this->postMeta[1]['woosb_shipping_fee'] = 'each';

		$this->givenCart( $this->bundleCart( true, array( 'woosb_price' => '50' ) ) );

		self::assertEquals(
			array( $this->apiItem( 2, 25.0, 2 ), $this->apiItem( 3, 0.0, 1 ), $this->apiItem( 4, 10.0, 1 ) ),
			$this->service->buildItems()
		);
	}

	public function test_build_items_ignores_bundle_keys_missing_from_cart(): void {
		$this->postMeta[1]['woosb_shipping_fee'] = 'each';

		$cart = $this->bundleCart( false, array( 'woosb_price' => '50' ) );
		unset( $cart['child-a'] );
		$this->givenCart( $cart );

		self::assertEquals(
			array( $this->apiItem( 3, 20.0, 1 ), $this->apiItem( 4, 10.0, 1 ) ),
			$this->service->buildItems()
		);
	}

	public function test_build_items_divides_component_line_total_by_its_quantity(): void {
		$this->postMeta[1]['woosb_shipping_fee'] = 'each';

		$parent = $this->mockProduct( 1, '0', array(), 'woosb', self::WOOSB );
		$parent->allows( 'is_fixed_price' )->andReturn( false );

		$this->givenCart(
			array(
				'kit'   => $this->cartItem( $parent, 1, array( 'woosb_keys' => array( 'child' ), 'woosb_price' => '9' ) ),
				'child' => $this->cartItem( $this->mockProduct( 2 ), 0, array( 'line_total' => 9, 'woosb_parent_id' => 1 ) ),
			)
		);

		self::assertEquals( array( $this->apiItem( 2, 9.0, 0 ) ), $this->service->buildItems() );
	}

	public function test_build_items_quotes_bundle_per_kit_value_when_quantity_above_one(): void {
		$this->markTestIncomplete(
			'BUG: o preço do WPC Bundle/Composite é por unidade, mas é dividido pela quantidade de novo. '
			. 'Correção: fix/wpc-bundle-composite-unit-price.'
		);

		$parent = $this->mockProduct( 1, '0', array(), 'woosb', self::WOOSB );
		$parent->allows( 'is_fixed_price' )->andReturn( false );

		$this->givenCart(
			array(
				'kit' => $this->cartItem( $parent, 2, array( 'woosb_keys' => array(), 'woosb_price' => '50', 'line_total' => 0 ) ),
			)
		);

		self::assertEquals( array( $this->apiItem( 1, 50.0, 2 ) ), $this->service->buildItems() );
	}

	public function test_build_items_expands_composite_components_when_shipping_fee_is_each(): void {
		$this->postMeta[1] = array(
			'wooco_shipping_fee' => 'each',
			'wooco_pricing'      => 'exclude',
		);

		$this->givenCart( $this->compositeCart( array( 'wooco_price' => '60' ) ) );

		self::assertEquals(
			array( $this->apiItem( 2, 15.0, 2 ), $this->apiItem( 3, 30.0, 1 ) ),
			$this->service->buildItems()
		);
	}

	/**
	 * @dataProvider dumpingCompositePricings
	 */
	public function test_build_items_dumps_composite_value_into_first_component( string $pricing ): void {
		$this->postMeta[1] = array(
			'wooco_shipping_fee' => 'each',
			'wooco_pricing'      => $pricing,
		);

		$this->givenCart( $this->compositeCart( array( 'wooco_price' => '80' ) ) );

		self::assertEquals(
			array( $this->apiItem( 2, 40.0, 2 ), $this->apiItem( 3, 0.0, 1 ) ),
			$this->service->buildItems()
		);
	}

	public function dumpingCompositePricings(): array {
		return array(
			'include' => array( 'include' ),
			'only'    => array( 'only' ),
		);
	}

	public function test_build_items_quotes_composite_parent_with_composite_price(): void {
		$this->postMeta[1]['wooco_pricing'] = 'include';

		$this->givenCart( $this->compositeCart( array( 'wooco_price' => '80' ) ) );

		self::assertEquals( array( $this->apiItem( 1, 80.0, 1 ) ), $this->service->buildItems() );
	}

	public function test_build_items_falls_back_to_composite_line_total_without_composite_price(): void {
		$this->givenCart( $this->compositeCart( array( 'wooco_price' => '', 'line_total' => 44 ) ) );

		self::assertEquals( array( $this->apiItem( 1, 44.0, 1 ) ), $this->service->buildItems() );
	}

	public function test_build_items_quotes_composite_per_unit_value_when_quantity_above_one(): void {
		$this->markTestIncomplete(
			'BUG: o preço do WPC Bundle/Composite é por unidade, mas é dividido pela quantidade de novo. '
			. 'Correção: fix/wpc-bundle-composite-unit-price.'
		);

		$this->givenCart(
			array(
				'comp' => $this->cartItem(
					$this->mockProduct( 1, '0', array(), 'composite' ),
					2,
					array( 'wooco_keys' => array(), 'wooco_price' => '80', 'line_total' => 0 )
				),
			)
		);

		self::assertEquals( array( $this->apiItem( 1, 80.0, 2 ) ), $this->service->buildItems() );
	}

	// ---------------------------------------------------------------------
	// buildItemsForBundleProduct (product page).
	// ---------------------------------------------------------------------

	public function test_bundle_product_builder_returns_single_item_for_non_bundle(): void {
		$product = $this->mockProduct( 1, '30' );

		self::assertEquals(
			array( $this->apiItem( 1, 30.0, 2 ) ),
			$this->service->buildItemsForBundleProduct( $product, 2 )
		);
	}

	public function test_bundle_product_builder_returns_single_item_when_bundle_lacks_get_items(): void {
		$product = $this->mockProduct( 1, '30', array(), 'woosb' );

		self::assertEquals(
			array( $this->apiItem( 1, 30.0, 1 ) ),
			$this->service->buildItemsForBundleProduct( $product, 1, '2/1' )
		);
	}

	public function test_bundle_product_builder_quotes_parent_with_sale_price(): void {
		$product = $this->mockBundle( array( array( 'id' => 2, 'qty' => 1 ) ), array( 'sale_price' => '45' ) );
		$product->shouldNotReceive( 'build_items' );

		self::assertEquals(
			array( $this->apiItem( 1, 45.0, 3 ) ),
			$this->service->buildItemsForBundleProduct( $product, 3 )
		);
	}

	/**
	 * @dataProvider emptySalePrices
	 *
	 * @param mixed $salePrice
	 */
	public function test_bundle_product_builder_falls_back_to_price_without_sale_price( $salePrice ): void {
		$product = $this->mockBundle( array(), array( 'sale_price' => $salePrice, 'price' => '60' ) );

		self::assertEquals(
			array( $this->apiItem( 1, 60.0, 2 ) ),
			$this->service->buildItemsForBundleProduct( $product, 2 )
		);
	}

	public function emptySalePrices(): array {
		return array(
			'empty string' => array( '' ),
			'null'         => array( null ),
		);
	}

	public function test_bundle_product_builder_falls_back_to_regular_price_when_price_is_empty(): void {
		$product = $this->mockBundle( array(), array( 'price' => '', 'regular_price' => '70' ) );

		self::assertEquals(
			array( $this->apiItem( 1, 70.0, 1 ) ),
			$this->service->buildItemsForBundleProduct( $product, 1 )
		);
	}

	public function test_bundle_product_builder_keeps_zero_sale_price(): void {
		$product = $this->mockBundle( array(), array( 'sale_price' => '0', 'price' => '60' ) );

		self::assertEquals(
			array( $this->apiItem( 1, 0.0, 1 ) ),
			$this->service->buildItemsForBundleProduct( $product, 1 )
		);
	}

	public function test_bundle_product_builder_applies_live_selection(): void {
		$product = $this->mockBundle( array(), array( 'sale_price' => '45' ) );
		$product->expects( 'build_items' )->with( '2/1/0,3/2/0' );

		$this->service->buildItemsForBundleProduct( $product, 1, '2/1/0,3/2/0' );
	}

	/**
	 * @dataProvider emptySelections
	 */
	public function test_bundle_product_builder_ignores_empty_selection( ?string $selection ): void {
		$product = $this->mockBundle( array(), array( 'sale_price' => '45' ) );
		$product->shouldNotReceive( 'build_items' );

		$this->service->buildItemsForBundleProduct( $product, 1, $selection );
	}

	public function emptySelections(): array {
		return array(
			'null'         => array( null ),
			'empty string' => array( '' ),
			'zero string'  => array( '0' ),
		);
	}

	public function test_bundle_product_builder_quotes_components_when_shipping_fee_is_each(): void {
		$this->postMeta[1]['woosb_shipping_fee'] = 'each';
		$this->givenCatalog( 2, '10' );
		$this->givenCatalog( 3, '25' );

		$product = $this->mockBundle(
			array(
				array( 'id' => 2, 'qty' => 2 ),
				array( 'id' => 3, 'qty' => '1' ),
			),
			array( 'sale_price' => '45' )
		);

		self::assertEquals(
			array( $this->apiItem( 2, 10.0, 6 ), $this->apiItem( 3, 25.0, 3 ) ),
			$this->service->buildItemsForBundleProduct( $product, 3 )
		);
	}

	public function test_bundle_product_builder_applies_discount_percentage_to_components(): void {
		$this->postMeta[1]['woosb_shipping_fee'] = 'each';
		$this->givenCatalog( 2, '10' );
		$this->givenCatalog( 3, '25' );

		$product = $this->mockBundle(
			array(
				array( 'id' => 2, 'qty' => 1 ),
				array( 'id' => 3, 'qty' => 1 ),
			),
			array( 'sale_price' => '31.5', 'discount' => '10' )
		);

		self::assertEqualsWithDelta(
			array( $this->apiItem( 2, 9.0, 1 ), $this->apiItem( 3, 22.5, 1 ) ),
			$this->service->buildItemsForBundleProduct( $product, 1 ),
			0.0001
		);
	}

	public function test_bundle_product_builder_skips_missing_components_and_defaults_quantity(): void {
		$this->postMeta[1]['woosb_shipping_fee'] = 'each';
		$this->givenCatalog( 3, '25' );

		$product = $this->mockBundle(
			array(
				array( 'id' => 2, 'qty' => 1 ),
				array( 'qty' => 1 ),
				array( 'id' => 3 ),
				array( 'id' => 3, 'qty' => 0 ),
				array( 'id' => 3, 'qty' => -4 ),
			),
			array( 'sale_price' => '45' )
		);

		self::assertEquals(
			array( $this->apiItem( 3, 25.0, 2 ), $this->apiItem( 3, 25.0, 2 ), $this->apiItem( 3, 25.0, 2 ) ),
			$this->service->buildItemsForBundleProduct( $product, 2 )
		);
	}

	public function test_bundle_product_builder_quotes_parent_when_no_component_resolves(): void {
		$this->postMeta[1]['woosb_shipping_fee'] = 'each';

		$product = $this->mockBundle( array( array( 'id' => 99, 'qty' => 1 ) ), array( 'sale_price' => '45' ) );

		self::assertEquals(
			array( $this->apiItem( 1, 45.0, 1 ) ),
			$this->service->buildItemsForBundleProduct( $product, 1 )
		);
	}

	public function test_bundle_product_builder_dumps_fixed_price_into_first_component(): void {
		$this->postMeta[1]['woosb_shipping_fee'] = 'each';
		$this->givenCatalog( 2, '10' );
		$this->givenCatalog( 3, '25' );

		$product = $this->mockBundle(
			array(
				array( 'id' => 2, 'qty' => 2 ),
				array( 'id' => 3, 'qty' => 1 ),
			),
			array( 'sale_price' => '', 'price' => '40', 'fixed' => true )
		);

		self::assertEquals(
			array( $this->apiItem( 2, 20.0, 4 ), $this->apiItem( 3, 0.0, 2 ) ),
			$this->service->buildItemsForBundleProduct( $product, 2 )
		);
	}

	// ---------------------------------------------------------------------
	// buildItemsForCompositeProduct (product page).
	// ---------------------------------------------------------------------

	public function test_composite_product_builder_returns_null_for_non_composite(): void {
		self::assertNull( $this->service->buildItemsForCompositeProduct( $this->mockProduct( 1, '10', array(), 'woosb' ), 1, '2/1' ) );
		self::assertSame( array(), \WPCleverWooco_Helper::$requested );
	}

	/**
	 * @dataProvider emptyCompositeSelections
	 */
	public function test_composite_product_builder_returns_null_without_selection( string $selection ): void {
		self::assertNull( $this->service->buildItemsForCompositeProduct( $this->mockComposite(), 1, $selection ) );
		self::assertSame( array(), \WPCleverWooco_Helper::$requested );
	}

	public function emptyCompositeSelections(): array {
		return array(
			'empty string' => array( '' ),
			'zero string'  => array( '0' ),
		);
	}

	public function test_composite_product_builder_returns_null_when_no_component_resolves(): void {
		\WPCleverWooco_Helper::$items = array( array( 'id' => 99, 'qty' => 1 ), array( 'qty' => 1 ) );

		self::assertNull( $this->service->buildItemsForCompositeProduct( $this->mockComposite(), 1, '99/1' ) );
		self::assertSame( array( '99/1' ), \WPCleverWooco_Helper::$requested );
	}

	/**
	 * @dataProvider compositePricingTotals
	 */
	public function test_composite_product_builder_quotes_parent_by_pricing_mode( string $pricing, float $expectedUnitValue ): void {
		$this->postMeta[1]['wooco_pricing'] = $pricing;
		$this->givenCompositeSelection();

		self::assertEqualsWithDelta(
			array( $this->apiItem( 1, $expectedUnitValue, 2 ) ),
			$this->service->buildItemsForCompositeProduct( $this->mockComposite( '20' ), 2, '2/2,3/1' ),
			0.0001
		);
	}

	public function compositePricingTotals(): array {
		// Parent R$20; components per composite: 2 x R$10 + 1 x R$25 = R$45.
		return array(
			'include'        => array( 'include', 65.0 ),
			'not configured' => array( '', 65.0 ),
			'exclude'        => array( 'exclude', 45.0 ),
			'only'           => array( 'only', 20.0 ),
		);
	}

	public function test_composite_product_builder_quotes_components_when_shipping_fee_is_each(): void {
		$this->postMeta[1] = array(
			'wooco_shipping_fee' => 'each',
			'wooco_pricing'      => 'exclude',
		);
		$this->givenCompositeSelection();

		self::assertEquals(
			array( $this->apiItem( 2, 10.0, 4 ), $this->apiItem( 3, 25.0, 2 ) ),
			$this->service->buildItemsForCompositeProduct( $this->mockComposite( '20' ), 2, '2/2,3/1' )
		);
	}

	/**
	 * @dataProvider dumpingCompositePricingTotals
	 */
	public function test_composite_product_builder_dumps_total_into_first_component( string $pricing, float $total ): void {
		$this->postMeta[1] = array(
			'wooco_shipping_fee' => 'each',
			'wooco_pricing'      => $pricing,
		);
		$this->givenCompositeSelection();

		self::assertEqualsWithDelta(
			array( $this->apiItem( 2, $total / 4, 4 ), $this->apiItem( 3, 0.0, 2 ) ),
			$this->service->buildItemsForCompositeProduct( $this->mockComposite( '20' ), 2, '2/2,3/1' ),
			0.0001
		);
	}

	public function dumpingCompositePricingTotals(): array {
		return array(
			'include' => array( 'include', 130.0 ),
			'only'    => array( 'only', 40.0 ),
		);
	}

	public function test_composite_product_builder_applies_discount_to_components(): void {
		$this->postMeta[1] = array(
			'wooco_shipping_fee' => 'each',
			'wooco_pricing'      => 'exclude',
		);
		$this->givenCompositeSelection();

		self::assertEqualsWithDelta(
			array( $this->apiItem( 2, 8.0, 2 ), $this->apiItem( 3, 20.0, 1 ) ),
			$this->service->buildItemsForCompositeProduct( $this->mockComposite( '20', '20' ), 1, '2/2,3/1' ),
			0.0001
		);
	}

	public function test_composite_product_builder_discount_does_not_touch_parent_base_price(): void {
		$this->postMeta[1]['wooco_pricing'] = 'include';
		$this->givenCompositeSelection();

		// Parent R$20 (undiscounted) + components R$45 discounted 20% (R$36).
		self::assertEqualsWithDelta(
			array( $this->apiItem( 1, 56.0, 1 ) ),
			$this->service->buildItemsForCompositeProduct( $this->mockComposite( '20', '20' ), 1, '2/2,3/1' ),
			0.0001
		);
	}

	// ---------------------------------------------------------------------
	// Helpers.
	// ---------------------------------------------------------------------

	/**
	 * @param array<string, mixed> $dimensions
	 * @return \Mockery\MockInterface&\WC_Product
	 */
	private function mockProduct( int $id, string $price = '10', array $dimensions = array(), string $type = 'simple', string $interface = '' ) {
		$product    = Mockery::mock( $interface === '' ? 'WC_Product' : 'WC_Product, ' . $interface );
		$dimensions = array_merge( self::DEFAULT_DIMENSIONS, $dimensions );

		$product->allows( 'get_id' )->andReturn( $id );
		$product->allows( 'get_type' )->andReturn( $type );
		$product->allows( 'get_price' )->andReturn( $price );
		$product->allows( 'get_width' )->andReturn( $dimensions['width'] );
		$product->allows( 'get_height' )->andReturn( $dimensions['height'] );
		$product->allows( 'get_length' )->andReturn( $dimensions['length'] );
		$product->allows( 'get_weight' )->andReturn( $dimensions['weight'] );

		return $product;
	}

	/**
	 * Bundle product with id 1.
	 *
	 * @param array<int, array<string, mixed>> $definition
	 * @param array<string, mixed>             $config sale_price, price, regular_price, discount, fixed.
	 * @return \Mockery\MockInterface&\WC_Product
	 */
	private function mockBundle( array $definition, array $config ) {
		$product = $this->mockProduct( 1, (string) ( $config['price'] ?? '100' ), array(), 'woosb', self::WOOSB );

		$product->allows( 'get_items' )->andReturn( $definition );
		$product->allows( 'get_sale_price' )->andReturn( array_key_exists( 'sale_price', $config ) ? $config['sale_price'] : '' );
		$product->allows( 'get_regular_price' )->andReturn( $config['regular_price'] ?? '' );
		$product->allows( 'get_discount_percentage' )->andReturn( $config['discount'] ?? 0 );
		$product->allows( 'is_fixed_price' )->andReturn( $config['fixed'] ?? false );

		return $product;
	}

	/**
	 * Composite product with id 1.
	 *
	 * @return \Mockery\MockInterface&\WC_Product
	 */
	private function mockComposite( string $price = '20', string $discount = '0' ) {
		$product = $this->mockProduct( 1, $price, array(), 'composite', self::WOOCO );
		$product->allows( 'get_discount' )->andReturn( $discount );

		return $product;
	}

	/**
	 * Selection: product 2 (R$10) x2 and product 3 (R$25) x1 per composite.
	 */
	private function givenCompositeSelection(): void {
		$this->givenCatalog( 2, '10' );
		$this->givenCatalog( 3, '25' );

		\WPCleverWooco_Helper::$items = array(
			array( 'id' => 2, 'qty' => 2 ),
			array( 'id' => 3, 'qty' => 1 ),
		);
	}

	private function givenCatalog( int $id, string $price ): void {
		$this->catalog[ $id ] = $this->mockProduct( $id, $price );
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
	 * @param mixed                $quantity
	 * @param array<string, mixed> $extra
	 */
	private function cartItem( $product, $quantity, array $extra = array() ): array {
		return array_merge(
			array(
				'data'       => $product,
				'quantity'   => $quantity,
				'line_total' => 0,
			),
			$extra
		);
	}

	/**
	 * Bundle (id 1) with children 2 (x2, line R$30) and 3 (x1, line R$20), plus an unrelated
	 * simple product 4 (R$10).
	 *
	 * @param array<string, mixed> $parentExtra
	 */
	private function bundleCart( bool $fixedPrice, array $parentExtra ): array {
		$parent = $this->mockProduct( 1, '0', array(), 'woosb', self::WOOSB );
		$parent->allows( 'is_fixed_price' )->andReturn( $fixedPrice );

		return array(
			'kit'     => $this->cartItem( $parent, 1, array_merge( array( 'woosb_keys' => array( 'child-a', 'child-b' ) ), $parentExtra ) ),
			'child-a' => $this->cartItem( $this->mockProduct( 2 ), 2, array( 'line_total' => 30, 'woosb_parent_id' => 1 ) ),
			'child-b' => $this->cartItem( $this->mockProduct( 3 ), 1, array( 'line_total' => 20, 'woosb_parent_id' => 1 ) ),
			'simple'  => $this->cartItem( $this->mockProduct( 4 ), 1 ),
		);
	}

	/**
	 * Composite (id 1) with components 2 (x2, line R$30) and 3 (x1, line R$30).
	 *
	 * @param array<string, mixed> $parentExtra
	 */
	private function compositeCart( array $parentExtra ): array {
		return array(
			'comp'   => $this->cartItem(
				$this->mockProduct( 1, '0', array(), 'composite' ),
				1,
				array_merge( array( 'wooco_keys' => array( 'comp-a', 'comp-b' ) ), $parentExtra )
			),
			'comp-a' => $this->cartItem( $this->mockProduct( 2 ), 2, array( 'line_total' => 30, 'wooco_parent_id' => 1 ) ),
			'comp-b' => $this->cartItem( $this->mockProduct( 3 ), 1, array( 'line_total' => 30, 'wooco_parent_id' => 1 ) ),
		);
	}

	private function apiItem( int $id, float $insurance, int $quantity ): array {
		return array(
			'id'              => $id,
			'width'           => 11.0,
			'height'          => 3.0,
			'length'          => 16.0,
			'weight'          => 0.3,
			'insurance_value' => $insurance,
			'quantity'        => $quantity,
		);
	}
}
