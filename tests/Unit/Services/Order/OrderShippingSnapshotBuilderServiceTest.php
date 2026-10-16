<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Services\Order;

use Brain\Monkey\Functions;
use MelhorEnvio\Services\Order\OrderItemsBuilderService;
use MelhorEnvio\Services\Order\OrderShippingSnapshotBuilderService;
use MelhorEnvio\Services\Settings\IntegradorSettingsService;
use MelhorEnvio\Services\Shipping\CartItemsBuilderService;
use MelhorEnvio\Tests\TestCase;
use Mockery;

/**
 * OrderItemsBuilderService (and its own deps) are final, so the real chain is used and the
 * products are fed through mocked order line items.
 */
final class OrderShippingSnapshotBuilderServiceTest extends TestCase {

	private const NOW = '2026-10-08 12:00:00';

	private OrderShippingSnapshotBuilderService $service;

	protected function setUp(): void {
		parent::setUp();

		$this->service = new OrderShippingSnapshotBuilderService(
			new OrderItemsBuilderService( new CartItemsBuilderService( new IntegradorSettingsService() ) )
		);

		Functions\when( 'get_option' )->alias(
			static function ( $name, $default = false ) {
				$options = array(
					'melhor_envio_integrador_settings' => array(
						'dimensions_default' => array(
							'width'  => 10,
							'height' => 5,
							'length' => 20,
							'weight' => 1,
						),
					),
				);

				return $options[ $name ] ?? $default;
			}
		);
		Functions\when( 'wc_get_weight' )->returnArg( 1 );
		Functions\when( 'wc_get_dimension' )->returnArg( 1 );
		Functions\when( 'get_post_meta' )->justReturn( '' );
		Functions\when( 'current_time' )->justReturn( self::NOW );
	}

	public function test_builds_complete_snapshot(): void {
		$lineItem = $this->mockLineItem( 501, 'Camiseta', $this->mockProduct( 10, array( '15.4', '4.6', '25.5', '0.8' ) ), 2, 100.0 );
		$order    = $this->mockOrder(
			array( $lineItem ),
			array( $this->mockShippingItem( 'melhor_envio', $this->serviceMeta(), '23.90' ) ),
			array(
				'_me_invoice_key'       => '35240612345678000190550010000001231234567890',
				'_me_invoice_xml_danfe' => '<nfe/>',
				'melhorenvio_status_v2' => array(
					'order_id'    => 'abc-123',
					'purchase_id' => 'pur-1',
					'protocol'    => 'ORD-2026',
					'status'      => 'paid',
				),
			)
		);

		self::assertSame(
			array(
				'service_id'        => 2,
				'service_name'      => 'SEDEX',
				'company_id'        => 1,
				'company_name'      => 'Correios',
				'price'             => '23.90',
				'delivery_time'     => 3,
				'me_purchase'       => array(
					'order_id'    => 'abc-123',
					'purchase_id' => 'pur-1',
					'protocol'    => 'ORD-2026',
					'status'      => 'paid',
				),
				'date_quotation'    => self::NOW,
				'invoice_key'       => '35240612345678000190550010000001231234567890',
				'invoice_xml_danfe' => '<nfe/>',
				'products'          => array(
					array(
						'id'              => 501,
						'product_id'      => 10,
						'name'            => 'Camiseta',
						'quantity'        => 2,
						'price'           => 50.0,
						'weight'          => 0.8,
						'height'          => 5,
						'width'           => 15,
						'length'          => 26,
						'insurance_value' => 50.0,
					),
				),
			),
			$this->service->buildSnapshot( $order )
		);
	}

	public function test_service_data_is_empty_without_shipping_items(): void {
		$snapshot = $this->service->buildSnapshot( $this->mockOrder( array(), array(), array() ) );

		self::assertSame( $this->emptyServiceData(), $this->serviceData( $snapshot ) );
	}

	public function test_service_data_is_empty_for_other_shipping_methods(): void {
		$order = $this->mockOrder( array(), array( $this->mockShippingItem( 'flat_rate', $this->serviceMeta(), '10.00' ) ), array() );

		self::assertSame( $this->emptyServiceData(), $this->serviceData( $this->service->buildSnapshot( $order ) ) );
	}

	public function test_service_data_is_empty_when_service_id_is_missing(): void {
		$order = $this->mockOrder( array(), array( $this->mockShippingItem( 'melhor_envio', array(), '10.00' ) ), array() );

		self::assertSame( $this->emptyServiceData(), $this->serviceData( $this->service->buildSnapshot( $order ) ) );
	}

	public function test_only_first_shipping_item_is_considered(): void {
		$order = $this->mockOrder(
			array(),
			array(
				7 => $this->mockShippingItem( 'local_pickup', array(), '0' ),
				8 => $this->mockShippingItem( 'melhor_envio', $this->serviceMeta(), '23.90' ),
			),
			array()
		);

		self::assertSame( $this->emptyServiceData(), $this->serviceData( $this->service->buildSnapshot( $order ) ) );
	}

	/**
	 * @dataProvider statusWithoutPurchase
	 *
	 * @param mixed $status
	 */
	public function test_me_purchase_is_null_without_purchased_order( $status ): void {
		$snapshot = $this->service->buildSnapshot( $this->mockOrder( array(), array(), array( 'melhorenvio_status_v2' => $status ) ) );

		self::assertNull( $snapshot['me_purchase'] );
	}

	public function statusWithoutPurchase(): array {
		return array(
			'meta missing'     => array( '' ),
			'not an array'     => array( 'paid' ),
			'without order id' => array( array( 'status' => 'pending' ) ),
			'empty order id'   => array( array( 'order_id' => '' ) ),
		);
	}

	public function test_me_purchase_fills_missing_fields_with_null(): void {
		$snapshot = $this->service->buildSnapshot(
			$this->mockOrder( array(), array(), array( 'melhorenvio_status_v2' => array( 'order_id' => 'abc' ) ) )
		);

		self::assertSame(
			array(
				'order_id'    => 'abc',
				'purchase_id' => null,
				'protocol'    => null,
				'status'      => null,
			),
			$snapshot['me_purchase']
		);
	}

	public function test_invoice_fields_default_to_empty_strings(): void {
		$snapshot = $this->service->buildSnapshot( $this->mockOrder( array(), array(), array() ) );

		self::assertSame( '', $snapshot['invoice_key'] );
		self::assertSame( '', $snapshot['invoice_xml_danfe'] );
		self::assertSame( array(), $snapshot['products'] );
	}

	public function test_variation_line_items_are_matched_by_variation_id(): void {
		$lineItem = $this->mockLineItem( 601, 'Camiseta - Azul', $this->mockProduct( 55 ), 1, 30.0, 10, 55 );
		$order    = $this->mockOrder( array( $lineItem ), array(), array() );

		$product = $this->service->buildSnapshot( $order )['products'][0];

		self::assertSame( 601, $product['id'] );
		self::assertSame( 55, $product['product_id'] );
		self::assertSame( 'Camiseta - Azul', $product['name'] );
	}

	public function test_products_without_matching_line_item_fall_back_to_product_id(): void {
		// Product id differs from the ids stored on the line item (e.g. product was replaced).
		$lineItem = $this->mockLineItem( 701, 'Antigo', $this->mockProduct( 99 ), 1, 30.0, 10, 0 );
		$order    = $this->mockOrder( array( $lineItem ), array(), array() );

		$product = $this->service->buildSnapshot( $order )['products'][0];

		self::assertSame( 99, $product['id'] );
		self::assertSame( 99, $product['product_id'] );
		self::assertSame( '', $product['name'] );
	}

	private function serviceMeta(): array {
		return array(
			'me_service_id'    => '2',
			'me_service_name'  => 'SEDEX',
			'me_company_id'    => '1',
			'me_company_name'  => 'Correios',
			'me_delivery_time' => '3',
		);
	}

	private function emptyServiceData(): array {
		return array(
			'service_id'    => null,
			'service_name'  => '',
			'company_id'    => 0,
			'company_name'  => '',
			'price'         => '',
			'delivery_time' => 0,
		);
	}

	private function serviceData( array $snapshot ): array {
		return array_intersect_key( $snapshot, $this->emptyServiceData() );
	}

	private function mockOrder( array $lineItems, array $shippingItems, array $meta ): \WC_Order {
		$order = Mockery::mock( 'WC_Order' );
		$order->allows( 'get_items' )->andReturnUsing(
			static function ( $type = 'line_item' ) use ( $lineItems, $shippingItems ) {
				return $type === 'shipping' ? $shippingItems : $lineItems;
			}
		);
		$order->allows( 'get_meta' )->andReturnUsing(
			static function ( $key ) use ( $meta ) {
				return $meta[ $key ] ?? '';
			}
		);

		return $order;
	}

	private function mockShippingItem( string $methodId, array $meta, string $total ): \WC_Order_Item_Shipping {
		$item = Mockery::mock( 'WC_Order_Item_Shipping' );
		$item->allows( 'get_method_id' )->andReturn( $methodId );
		$item->allows( 'get_total' )->andReturn( $total );
		$item->allows( 'get_meta' )->andReturnUsing(
			static function ( $key ) use ( $meta ) {
				return $meta[ $key ] ?? '';
			}
		);

		return $item;
	}

	private function mockLineItem(
		int $id,
		string $name,
		\WC_Product $product,
		int $quantity,
		float $total,
		?int $productId = null,
		int $variationId = 0
	): \WC_Order_Item_Product {
		$item = Mockery::mock( 'WC_Order_Item_Product' );
		$item->allows( 'get_id' )->andReturn( $id );
		$item->allows( 'get_name' )->andReturn( $name );
		$item->allows( 'get_product' )->andReturn( $product );
		$item->allows( 'get_product_id' )->andReturn( $productId ?? $product->get_id() );
		$item->allows( 'get_variation_id' )->andReturn( $variationId );
		$item->allows( 'get_quantity' )->andReturn( $quantity );
		$item->allows( 'get_total' )->andReturn( (string) $total );
		$item->allows( 'get_meta' )->andReturn( '' );

		return $item;
	}

	private function mockProduct( int $id, array $dimensions = array( '1', '1', '1', '1' ) ): \WC_Product {
		$product = Mockery::mock( 'WC_Product' );
		$product->allows( 'get_id' )->andReturn( $id );
		$product->allows( 'get_type' )->andReturn( 'simple' );
		$product->allows( 'get_width' )->andReturn( $dimensions[0] );
		$product->allows( 'get_height' )->andReturn( $dimensions[1] );
		$product->allows( 'get_length' )->andReturn( $dimensions[2] );
		$product->allows( 'get_weight' )->andReturn( $dimensions[3] );

		return $product;
	}
}
