<?php

declare(strict_types=1);

namespace MelhorEnvio\Infrastructure\WordPress\RestApi;

use MelhorEnvio\Infrastructure\WordPress\Shipping\OrderShippingSnapshotBuilder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class OrderMetaBackfill {

	private OrderShippingSnapshotBuilder $snapshotBuilder;

	public function __construct( OrderShippingSnapshotBuilder $snapshotBuilder ) {
		$this->snapshotBuilder = $snapshotBuilder;
	}

	public function register(): void {
		add_filter( 'woocommerce_rest_prepare_shop_order_object', array( $this, 'refresh' ), 10, 2 );
	}

	public function refresh( \WP_REST_Response $response, \WC_Order $order ): \WP_REST_Response {
		$data = $this->snapshotBuilder->buildSnapshot( $order );

		$order->update_meta_data( OrderShippingSnapshotBuilder::META_KEY, $data );
		$order->save_meta_data();

		$responseData              = $response->get_data();
		$responseData['meta_data'] = array_values(
			array_filter(
				$responseData['meta_data'] ?? array(),
				static fn( $meta ): bool => ( is_array( $meta ) ? ( $meta['key'] ?? null ) : ( $meta->key ?? null ) ) !== OrderShippingSnapshotBuilder::META_KEY
			)
		);
		$responseData['meta_data'][] = array(
			'id'    => 0,
			'key'   => OrderShippingSnapshotBuilder::META_KEY,
			'value' => $data,
		);
		$response->set_data( $responseData );

		return $response;
	}
}
