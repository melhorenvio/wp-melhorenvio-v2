<?php

declare(strict_types=1);

namespace MelhorEnvio\Infrastructure\WordPress\RestApi;

use MelhorEnvio\Infrastructure\WordPress\Checkout\CheckoutOrderHandler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class OrderMetaBackfill {

	private CheckoutOrderHandler $checkoutOrderHandler;

	public function __construct( CheckoutOrderHandler $checkoutOrderHandler ) {
		$this->checkoutOrderHandler = $checkoutOrderHandler;
	}

	public function register(): void {
		add_filter( 'woocommerce_rest_prepare_shop_order_object', array( $this, 'maybeBackfill' ), 10, 2 );
	}

	public function maybeBackfill( \WP_REST_Response $response, \WC_Order $order ): \WP_REST_Response {
		if ( $order->get_meta( CheckoutOrderHandler::META_KEY, true ) ) {
			return $response;
		}

		$data = $this->checkoutOrderHandler->buildBackfillSnapshot( $order );

		$order->update_meta_data( CheckoutOrderHandler::META_KEY, $data );
		$order->save_meta_data();

		$responseData                = $response->get_data();
		$responseData['meta_data'][] = array(
			'id'    => 0,
			'key'   => CheckoutOrderHandler::META_KEY,
			'value' => $data,
		);
		$response->set_data( $responseData );

		return $response;
	}
}
