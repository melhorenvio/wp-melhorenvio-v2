<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Http\Controllers\Order;

use Brain\Monkey\Functions;
use MelhorEnvio\Http\Controllers\Order\OrderInvoiceKeyMetaBoxController;
use MelhorEnvio\Http\Controllers\Order\OrderMetaBackfillController;
use MelhorEnvio\Services\Order\ForceImportService;
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

		$this->controller = new OrderMetaBackfillController( $this->buildSnapshotBuilder(), new ForceImportService() );
	}

	public function test_register_hooks_into_rest_order_response(): void {
		$this->controller->register();

		self::assertSame(
			10,
			has_filter( 'woocommerce_rest_prepare_shop_order_object', array( $this->controller, 'refresh' ) )
		);
	}

	public function test_does_not_write_to_order_on_read(): void {
		$order = $this->mockOrder();
		$order->shouldNotReceive( 'update_meta_data' );
		$order->shouldNotReceive( 'save_meta_data' );
		$order->shouldNotReceive( 'save' );

		$result = $this->controller->refresh( new \WP_REST_Response( array( 'id' => 1 ) ), $order );

		self::assertSame(
			array(
				array( 'id' => 0, 'key' => self::META_KEY, 'value' => $this->expectedSnapshot() ),
			),
			$result->get_data()['meta_data']
		);
	}

	public function test_replaces_stale_snapshot_in_response_meta_data(): void {
		$order = $this->mockOrder();

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

		$result = $this->controller->refresh( new \WP_REST_Response( array( 'id' => 1 ) ), $order );

		self::assertSame(
			array(
				array( 'id' => 0, 'key' => self::META_KEY, 'value' => $this->expectedSnapshot() ),
			),
			$result->get_data()['meta_data']
		);
	}

	public function test_injects_force_import_mark_while_active(): void {
		$expiresAt = time() + 600;
		$order     = $this->mockOrder( (string) $expiresAt );

		$response = new \WP_REST_Response(
			array(
				'id'        => 1,
				'meta_data' => array(
					array( 'id' => 3, 'key' => 'other_meta', 'value' => 'keep' ),
					array( 'id' => 7, 'key' => ForceImportService::META_KEY, 'value' => (string) $expiresAt ),
					array( 'id' => 8, 'key' => ForceImportService::PAYLOAD_KEY, 'value' => 'forged' ),
				),
			)
		);

		$result = $this->controller->refresh( $response, $order );

		self::assertSame(
			array(
				array( 'id' => 3, 'key' => 'other_meta', 'value' => 'keep' ),
				array( 'id' => 0, 'key' => self::META_KEY, 'value' => $this->expectedSnapshot() ),
				array( 'id' => 0, 'key' => ForceImportService::PAYLOAD_KEY, 'value' => $expiresAt ),
			),
			$result->get_data()['meta_data']
		);
	}

	public function test_strips_force_import_mark_when_expired(): void {
		$expiredAt = (string) ( time() - 1 );
		$order     = $this->mockOrder( $expiredAt );

		$markAsObject        = new \stdClass();
		$markAsObject->id    = 7;
		$markAsObject->key   = ForceImportService::META_KEY;
		$markAsObject->value = $expiredAt;

		$response = new \WP_REST_Response(
			array(
				'id'        => 1,
				'meta_data' => array(
					$markAsObject,
					array( 'id' => 8, 'key' => ForceImportService::PAYLOAD_KEY, 'value' => (int) $expiredAt ),
				),
			)
		);

		$result = $this->controller->refresh( $response, $order );

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
	private function mockOrder( string $forceImportExpiresAt = '' ) {
		$order = Mockery::mock( 'WC_Order' );
		$order->allows( 'get_meta' )->with( ForceImportService::META_KEY, true )->andReturn( $forceImportExpiresAt );
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
