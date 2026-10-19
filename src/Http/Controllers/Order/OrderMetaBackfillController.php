<?php

declare(strict_types=1);

namespace MelhorEnvio\Http\Controllers\Order;

use MelhorEnvio\Http\Controllers\Contracts\ControllerInterface;
use MelhorEnvio\Services\Order\OrderShippingSnapshotBuilderService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class OrderMetaBackfillController implements ControllerInterface {

	private OrderShippingSnapshotBuilderService $snapshotBuilder;

	public function __construct( OrderShippingSnapshotBuilderService $snapshotBuilder ) {
		$this->snapshotBuilder = $snapshotBuilder;
	}

	public function register(): void {
		add_filter( 'woocommerce_rest_prepare_shop_order_object', array( $this, 'refresh' ), 10, 2 );
	}

	public function refresh( \WP_REST_Response $response, \WC_Order $order ): \WP_REST_Response {
		$data = $this->snapshotBuilder->buildSnapshot( $order );

		$order->update_meta_data( OrderShippingSnapshotBuilderService::META_KEY, $data );
		$order->save_meta_data();

		$responseData              = $response->get_data();
		$responseData['meta_data'] = array_values(
			array_filter(
				$responseData['meta_data'] ?? array(),
				static fn( $meta ): bool => ( is_array( $meta ) ? ( $meta['key'] ?? null ) : ( $meta->key ?? null ) ) !== OrderShippingSnapshotBuilderService::META_KEY
			)
		);
		$responseData['meta_data'][] = array(
			'id'    => 0,
			'key'   => OrderShippingSnapshotBuilderService::META_KEY,
			'value' => $data,
		);
		$response->set_data( $responseData );

		return $response;
	}
}
