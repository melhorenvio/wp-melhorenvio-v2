<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Services\Order;

use Brain\Monkey\Functions;
use MelhorEnvio\Services\Order\OrderItemsBuilderService;
use MelhorEnvio\Services\Settings\IntegradorSettingsService;
use MelhorEnvio\Services\Shipping\CartItemsBuilderService;
use MelhorEnvio\Tests\TestCase;
use Mockery;

require_once dirname( __DIR__, 3 ) . '/Stubs/Extra/OrderItemsBuilderServiceTest.php';

/**
 * CartItemsBuilderService and IntegradorSettingsService are final, so the real ones are used;
 * only the WordPress functions they reach (get_option, get_post_meta, wc_get_*) are mocked.
 */
final class OrderItemsBuilderServiceTest extends TestCase {

	private OrderItemsBuilderService $service;

	/** @var array<int, array<string, mixed>> Post meta per product id. */
	private array $postMeta = array();

	protected function setUp(): void {
		parent::setUp();

		$this->service = new OrderItemsBuilderService( new CartItemsBuilderService( new IntegradorSettingsService() ) );

		Functions\when( 'get_option' )->alias(
			static function ( $name, $default = false ) {
				$options = array(
					'melhor_envio_integrador_settings' => array(
						'dimensions_default' => array(
							'width'  => 10,
							'height' => 5,
							'length' => 20,
							'weight' => 1.5,
						),
					),
					'woocommerce_weight_unit'          => 'kg',
					'woocommerce_dimension_unit'       => 'cm',
				);

				return $options[ $name ] ?? $default;
			}
		);
		Functions\when( 'wc_get_weight' )->returnArg( 1 );
		Functions\when( 'wc_get_dimension' )->returnArg( 1 );
		Functions\when( 'get_post_meta' )->alias(
			function ( $id, $key = '', $single = false ) {
				return $this->postMeta[ $id ][ $key ] ?? '';
			}
		);
	}

	public function test_returns_empty_list_for_order_without_items(): void {
		self::assertSame( array(), $this->service->buildItems( $this->mockOrder( array() ) ) );
	}

	public function test_builds_simple_item_with_unit_value_from_line_total(): void {
		$product = $this->mockProduct( 10, 'simple', array( 15, 4, 25, 0.8 ) );
		$order   = $this->mockOrder( array( $this->mockLineItem( 1, $product, 2, 50.0 ) ) );

		self::assertSame(
			array( $this->apiItem( 10, array( 15, 4, 25, 0.8 ), 25.0, 2 ) ),
			$this->service->buildItems( $order )
		);
	}

	public function test_uses_default_dimensions_when_product_has_none(): void {
		$product = $this->mockProduct( 10, 'simple', array( '', '', '', '' ) );
		$order   = $this->mockOrder( array( $this->mockLineItem( 1, $product, 1, 30.0 ) ) );

		self::assertSame(
			array( $this->apiItem( 10, array( 10, 5, 20, 1.5 ), 30.0, 1 ) ),
			$this->service->buildItems( $order )
		);
	}

	public function test_treats_zero_quantity_as_one(): void {
		$product = $this->mockProduct( 10, 'simple' );
		$order   = $this->mockOrder( array( $this->mockLineItem( 1, $product, 0, 40.0 ) ) );

		$items = $this->service->buildItems( $order );

		self::assertSame( 1, $items[0]['quantity'] );
		self::assertSame( 40.0, $items[0]['insurance_value'] );
	}

	public function test_skips_line_items_whose_product_no_longer_exists(): void {
		$product = $this->mockProduct( 10, 'simple' );
		$order   = $this->mockOrder(
			array(
				$this->mockLineItem( 1, false, 1, 10.0 ),
				$this->mockLineItem( 2, $product, 1, 20.0 ),
			)
		);

		$items = $this->service->buildItems( $order );

		self::assertCount( 1, $items );
		self::assertSame( 10, $items[0]['id'] );
	}

	public function test_bundle_quoted_as_parent_uses_woosb_price_as_unit_value(): void {
		$this->markTestIncomplete(
			'BUG: o preço do WPC Bundle/Composite é por unidade, mas é dividido pela quantidade de novo. '
			. 'Correção: fix/wpc-bundle-composite-unit-price.'
		);

		$bundle = $this->mockProduct( 100, 'woosb', array( 30, 10, 40, 2 ) );
		$order  = $this->mockOrder(
			array(
				$this->mockLineItem( 1, $bundle, 2, 0.0, array( '_woosb_price' => '120' ) ),
				$this->mockLineItem( 2, $this->mockProduct( 11, 'simple' ), 2, 60.0, array( '_woosb_parent_id' => '100' ) ),
				$this->mockLineItem( 3, $this->mockProduct( 12, 'simple' ), 2, 60.0, array( '_woosb_parent_id' => '100' ) ),
			)
		);

		self::assertSame(
			array( $this->apiItem( 100, array( 30, 10, 40, 2 ), 120.0, 2 ) ),
			$this->service->buildItems( $order )
		);
	}

	public function test_bundle_without_woosb_price_falls_back_to_line_total(): void {
		$bundle = $this->mockProduct( 100, 'product-woosb' );
		$order  = $this->mockOrder( array( $this->mockLineItem( 1, $bundle, 1, 99.0 ) ) );

		$items = $this->service->buildItems( $order );

		self::assertCount( 1, $items );
		self::assertSame( 100, $items[0]['id'] );
		self::assertSame( 99.0, $items[0]['insurance_value'] );
	}

	public function test_bundle_with_each_shipping_fee_is_expanded_into_its_components(): void {
		$this->postMeta[100]['woosb_shipping_fee'] = 'each';

		$bundle = $this->mockProduct( 100, 'woosb' );
		$order  = $this->mockOrder(
			array(
				$this->mockLineItem( 1, $bundle, 1, 0.0, array( '_woosb_price' => '90' ) ),
				$this->mockLineItem( 2, $this->mockProduct( 11, 'simple' ), 2, 60.0, array( '_woosb_parent_id' => '100' ) ),
				$this->mockLineItem( 3, $this->mockProduct( 12, 'simple' ), 1, 30.0, array( '_woosb_parent_id' => '100' ) ),
				$this->mockLineItem( 4, $this->mockProduct( 13, 'simple' ), 1, 15.0 ),
			)
		);

		$items = $this->service->buildItems( $order );

		self::assertSame( array( 11, 12, 13 ), array_column( $items, 'id' ) );
		self::assertSame( array( 2, 1, 1 ), array_column( $items, 'quantity' ) );
		self::assertSame( array( 30.0, 30.0, 15.0 ), array_column( $items, 'insurance_value' ) );
	}

	public function test_fixed_price_bundle_puts_kit_value_into_first_component(): void {
		$this->postMeta[100]['woosb_shipping_fee'] = 'each';

		$bundle = Mockery::mock( 'WC_Product, MelhorEnvioTestsWoosbFixedPriceProduct' );
		$this->stubProduct( $bundle, 100, 'woosb', array( 1, 1, 1, 1 ) );
		$bundle->allows( 'is_fixed_price' )->andReturn( true );

		$order = $this->mockOrder(
			array(
				$this->mockLineItem( 1, $bundle, 1, 80.0 ),
				$this->mockLineItem( 2, $this->mockProduct( 11, 'simple' ), 2, 0.0, array( '_woosb_parent_id' => '100' ) ),
				$this->mockLineItem( 3, $this->mockProduct( 12, 'simple' ), 1, 0.0, array( '_woosb_parent_id' => '100' ) ),
			)
		);

		$items = $this->service->buildItems( $order );

		self::assertSame( array( 11, 12 ), array_column( $items, 'id' ) );
		self::assertSame( array( 40.0, 0.0 ), array_column( $items, 'insurance_value' ) );
	}

	public function test_children_are_assigned_to_the_nearest_preceding_parent_of_the_same_bundle(): void {
		$this->postMeta[100]['woosb_shipping_fee'] = 'each';

		$bundle = $this->mockProduct( 100, 'woosb' );
		$order  = $this->mockOrder(
			array(
				$this->mockLineItem( 1, $bundle, 1, 0.0, array( '_woosb_price' => '30' ) ),
				$this->mockLineItem( 2, $this->mockProduct( 11, 'simple' ), 1, 10.0, array( '_woosb_parent_id' => '100' ) ),
				$this->mockLineItem( 3, $this->mockProduct( 12, 'simple' ), 1, 20.0, array( '_woosb_parent_id' => '100' ) ),
				$this->mockLineItem( 4, $bundle, 1, 0.0, array( '_woosb_price' => '70' ) ),
				$this->mockLineItem( 5, $this->mockProduct( 13, 'simple' ), 1, 70.0, array( '_woosb_parent_id' => '100' ) ),
			)
		);

		$items = $this->service->buildItems( $order );

		self::assertSame( array( 11, 12, 13 ), array_column( $items, 'id' ) );
		self::assertSame( array( 10.0, 20.0, 70.0 ), array_column( $items, 'insurance_value' ) );
	}

	public function test_bundle_component_without_product_is_ignored(): void {
		$this->postMeta[100]['woosb_shipping_fee'] = 'each';

		$order = $this->mockOrder(
			array(
				$this->mockLineItem( 1, $this->mockProduct( 100, 'woosb' ), 1, 0.0, array( '_woosb_price' => '30' ) ),
				$this->mockLineItem( 2, false, 1, 10.0, array( '_woosb_parent_id' => '100' ) ),
				$this->mockLineItem( 3, $this->mockProduct( 12, 'simple' ), 1, 20.0, array( '_woosb_parent_id' => '100' ) ),
			)
		);

		self::assertSame( array( 12 ), array_column( $this->service->buildItems( $order ), 'id' ) );
	}

	public function test_orphan_child_line_items_are_not_quoted(): void {
		$order = $this->mockOrder(
			array(
				$this->mockLineItem( 1, $this->mockProduct( 11, 'simple' ), 1, 10.0, array( '_woosb_parent_id' => '999' ) ),
				$this->mockLineItem( 2, $this->mockProduct( 12, 'simple' ), 1, 20.0, array( 'wooco_parent_id' => '998' ) ),
			)
		);

		self::assertSame( array(), $this->service->buildItems( $order ) );
	}

	public function test_composite_quoted_as_parent_uses_wooco_price(): void {
		$composite = $this->mockProduct( 200, 'composite', array( 20, 20, 20, 3 ) );
		$order     = $this->mockOrder(
			array(
				$this->mockLineItem( 1, $composite, 1, 0.0, array( 'wooco_price' => '150.5' ) ),
				$this->mockLineItem( 2, $this->mockProduct( 21, 'simple' ), 1, 100.0, array( 'wooco_parent_id' => '200' ) ),
			)
		);

		self::assertSame(
			array( $this->apiItem( 200, array( 20, 20, 20, 3 ), 150.5, 1 ) ),
			$this->service->buildItems( $order )
		);
	}

	public function test_composite_quoted_as_parent_uses_wooco_price_as_unit_value(): void {
		$this->markTestIncomplete(
			'BUG: o preço do WPC Bundle/Composite é por unidade, mas é dividido pela quantidade de novo. '
			. 'Correção: fix/wpc-bundle-composite-unit-price.'
		);

		$composite = $this->mockProduct( 200, 'composite', array( 20, 20, 20, 3 ) );
		$order     = $this->mockOrder(
			array( $this->mockLineItem( 1, $composite, 2, 0.0, array( 'wooco_price' => '80' ) ) )
		);

		self::assertSame(
			array( $this->apiItem( 200, array( 20, 20, 20, 3 ), 80.0, 2 ) ),
			$this->service->buildItems( $order )
		);
	}

	public function test_composite_without_wooco_price_falls_back_to_line_total(): void {
		$order = $this->mockOrder(
			array( $this->mockLineItem( 1, $this->mockProduct( 200, 'product-composite' ), 3, 90.0 ) )
		);

		$items = $this->service->buildItems( $order );

		self::assertSame( 200, $items[0]['id'] );
		self::assertSame( 3, $items[0]['quantity'] );
		self::assertSame( 30.0, $items[0]['insurance_value'] );
	}

	public function test_composite_with_each_shipping_fee_and_exclude_pricing_keeps_component_values(): void {
		$this->postMeta[200] = array(
			'wooco_shipping_fee' => 'each',
			'wooco_pricing'      => 'exclude',
		);

		$order = $this->mockOrder(
			array(
				$this->mockLineItem( 1, $this->mockProduct( 200, 'composite' ), 1, 0.0, array( 'wooco_price' => '50' ) ),
				$this->mockLineItem( 2, $this->mockProduct( 21, 'simple' ), 1, 20.0, array( 'wooco_parent_id' => '200' ) ),
				$this->mockLineItem( 3, $this->mockProduct( 22, 'simple' ), 2, 30.0, array( 'wooco_parent_id' => '200' ) ),
			)
		);

		$items = $this->service->buildItems( $order );

		self::assertSame( array( 21, 22 ), array_column( $items, 'id' ) );
		self::assertSame( array( 20.0, 15.0 ), array_column( $items, 'insurance_value' ) );
	}

	/**
	 * @dataProvider dumpIntoFirstPricingModes
	 */
	public function test_composite_with_each_shipping_fee_puts_total_into_first_component( string $pricing ): void {
		$this->postMeta[200] = array(
			'wooco_shipping_fee' => 'each',
			'wooco_pricing'      => $pricing,
		);

		$order = $this->mockOrder(
			array(
				$this->mockLineItem( 1, $this->mockProduct( 200, 'composite' ), 1, 0.0, array( 'wooco_price' => '80' ) ),
				$this->mockLineItem( 2, $this->mockProduct( 21, 'simple' ), 2, 20.0, array( 'wooco_parent_id' => '200' ) ),
				$this->mockLineItem( 3, $this->mockProduct( 22, 'simple' ), 1, 30.0, array( 'wooco_parent_id' => '200' ) ),
			)
		);

		$items = $this->service->buildItems( $order );

		self::assertSame( array( 21, 22 ), array_column( $items, 'id' ) );
		self::assertSame( array( 40.0, 0.0 ), array_column( $items, 'insurance_value' ) );
	}

	public function dumpIntoFirstPricingModes(): array {
		return array(
			'include' => array( 'include' ),
			'only'    => array( 'only' ),
		);
	}

	/**
	 * @param mixed $product WC_Product mock, or false for a deleted product.
	 */
	private function mockLineItem( int $id, $product, int $quantity, float $total, array $meta = array() ): \WC_Order_Item_Product {
		$item = Mockery::mock( 'WC_Order_Item_Product' );
		$item->allows( 'get_id' )->andReturn( $id );
		$item->allows( 'get_product' )->andReturn( $product );
		$item->allows( 'get_quantity' )->andReturn( $quantity );
		$item->allows( 'get_total' )->andReturn( (string) $total );
		$item->allows( 'get_meta' )->andReturnUsing(
			static function ( $key ) use ( $meta ) {
				return $meta[ $key ] ?? '';
			}
		);

		return $item;
	}

	/**
	 * @param array{0: mixed, 1: mixed, 2: mixed, 3: mixed} $dimensions width, height, length, weight.
	 */
	private function mockProduct( int $id, string $type, array $dimensions = array( 1, 1, 1, 1 ) ): \WC_Product {
		$product = Mockery::mock( 'WC_Product' );
		$this->stubProduct( $product, $id, $type, $dimensions );

		return $product;
	}

	/**
	 * @param \Mockery\MockInterface $product
	 */
	private function stubProduct( $product, int $id, string $type, array $dimensions ): void {
		$product->allows( 'get_id' )->andReturn( $id );
		$product->allows( 'get_type' )->andReturn( $type );
		$product->allows( 'get_width' )->andReturn( (string) $dimensions[0] );
		$product->allows( 'get_height' )->andReturn( (string) $dimensions[1] );
		$product->allows( 'get_length' )->andReturn( (string) $dimensions[2] );
		$product->allows( 'get_weight' )->andReturn( (string) $dimensions[3] );
	}

	private function mockOrder( array $lineItems ): \WC_Order {
		$order = Mockery::mock( 'WC_Order' );
		$order->allows( 'get_items' )->andReturn( $lineItems );

		return $order;
	}

	private function apiItem( int $id, array $dimensions, float $insuranceValue, int $quantity ): array {
		return array(
			'id'              => $id,
			'width'           => (float) $dimensions[0],
			'height'          => (float) $dimensions[1],
			'length'          => (float) $dimensions[2],
			'weight'          => (float) $dimensions[3],
			'insurance_value' => $insuranceValue,
			'quantity'        => $quantity,
		);
	}
}
