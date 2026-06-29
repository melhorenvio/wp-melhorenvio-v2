<?php

namespace MelhorEnvio\Controllers;

class ProductsController {

	/**
	 * Function to get insurance value by order.
	 *
	 * @param int $orderId
	 * @return float
	 */
	public function getInsuranceValue( $orderId ) {
		$order = wc_get_order( $orderId );
		$total = 0;

		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			$total   = $total + ( $product->get_price() * $item->get_quantity() );
		}

		return round( $total, 2 );
	}
}
