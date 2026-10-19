<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Http\Controllers\Order;

use Brain\Monkey\Functions;
use MelhorEnvio\Http\Controllers\Order\OrderInvoiceKeyMetaBoxController;
use MelhorEnvio\Http\Controllers\Order\OrderMetaBackfillController;
use MelhorEnvio\Services\Order\OrderItemsBuilderService;
use MelhorEnvio\Services\Order\OrderShippingSnapshotBuilderService;
use MelhorEnvio\Services\Shipping\CartItemsBuilderService;
use MelhorEnvio\Tests\TestCase;
use Mockery;

final class OrderMetaBackfillControllerTest extends TestCase {

	private const META_KEY    = OrderShippingSnapshotBuilderService::META_KEY;
	private const INVOICE_KEY = '35240612345678000190550010000001231234567890';
	private const NOW         = '2026-10-08 12:00:00';

	private OrderMetaBackfillController $controller;

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'current_time' )->justReturn( self::NOW );

		$this->controller = new OrderMetaBackfillController( $this->buildSnapshotBuilder() );
	}

	public function test_register_hooks_into_rest_order_response(): void {
		$this->controller->register();

		self::assertSame(
			10,
			has_filter( 'woocommerce_rest_prepare_shop_order_object', array( $this->controller, 'refresh' ) )
		);
	}

	public function test_persists_fresh_snapshot_on_order(): void {
		$order = $this->mockOrder();
		$order->expects( 'update_meta_data' )->with( self::META_KEY, $this->expectedSnapshot() );
		$order->expects( 'save_meta_data' );

		$this->controller->refresh( new \WP_REST_Response( array( 'id' => 1 ) ), $order );
	}

	public function test_replaces_stale_snapshot_in_response_meta_data(): void {
		$order = $this->mockOrder();
		$order->allows( 'update_meta_data' );
		$order->allows( 'save_meta_data' );

		$otherAsObject        = new \stdClass();
		$otherAsObject->id    = 5;
		$otherAsObject->key   = 'other_object_meta';
		$staleAsObject        = new \stdClass();
		$staleAsObject->id    = 6;
		$staleAsObject->key   = self::META_KEY;
		$staleAsObject->value = array( 'stale' => true );

		$response = new \WP_REST_Response(
			array(
				'id'        => 1,
				'meta_data' => array(
					array( 'id' => 3, 'key' => 'other_meta', 'value' => 'keep' ),
					array( 'id' => 4, 'key' => self::META_KEY, 'value' => array( 'stale' => true ) ),
					$otherAsObject,
					$staleAsObject,
				),
			)
		);

		$result = $this->controller->refresh( $response, $order );

		self::assertSame( $response, $result );
		self::assertSame( 1, $result->get_data()['id'] );
		self::assertEquals(
			array(
				array( 'id' => 3, 'key' => 'other_meta', 'value' => 'keep' ),
				$otherAsObject,
				array( 'id' => 0, 'key' => self::META_KEY, 'value' => $this->expectedSnapshot() ),
			),
			$result->get_data()['meta_data']
		);
	}

	public function test_adds_snapshot_when_response_has_no_meta_data(): void {
		$order = $this->mockOrder();
		$order->allows( 'update_meta_data' );
		$order->allows( 'save_meta_data' );

		$result = $this->controller->refresh( new \WP_REST_Response( array( 'id' => 1 ) ), $order );

		self::assertSame(
			array(
				array( 'id' => 0, 'key' => self::META_KEY, 'value' => $this->expectedSnapshot() ),
			),
			$result->get_data()['meta_data']
		);
	}

	/**
	 * The snapshot builder chain is made of final classes, so the real one is used with an order
	 * without items (CartItemsBuilderService is never reached and is built without its constructor).
	 */
	private function buildSnapshotBuilder(): OrderShippingSnapshotBuilderService {
		$cartItemsBuilder = ( new \ReflectionClass( CartItemsBuilderService::class ) )->newInstanceWithoutConstructor();

		return new OrderShippingSnapshotBuilderService( new OrderItemsBuilderService( $cartItemsBuilder ) );
	}

	/**
	 * @return \Mockery\MockInterface&\WC_Order
	 */
	private function mockOrder() {
		$order = Mockery::mock( 'WC_Order' );
		$order->allows( 'get_items' )->andReturn( array() );
		$order->allows( 'get_meta' )->with( 'melhorenvio_status_v2', true )->andReturn( '' );
		$order->allows( 'get_meta' )->with( OrderInvoiceKeyMetaBoxController::META_KEY, true )->andReturn( self::INVOICE_KEY );
		$order->allows( 'get_meta' )->with( '_me_invoice_xml_danfe', true )->andReturn( '<nfeProc/>' );

		return $order;
	}

	private function expectedSnapshot(): array {
		return array(
			'service_id'        => null,
			'service_name'      => '',
			'company_id'        => 0,
			'company_name'      => '',
			'price'             => '',
			'delivery_time'     => 0,
			'me_purchase'       => null,
			'date_quotation'    => self::NOW,
			'invoice_key'       => self::INVOICE_KEY,
			'invoice_xml_danfe' => '<nfeProc/>',
			'products'          => array(),
		);
	}
}
