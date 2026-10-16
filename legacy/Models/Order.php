<?php

namespace MelhorEnvio\Models;

use MelhorEnvio\Services\OrderQuotationService;

class Order {

	// Não confundir esses status com os status utlizados no Melhor Envio.

	const STATUS_GENERATED = 'generated';

	const STATUS_RELEASED = 'released';

	const STATUS_PAID = 'paid';

	const STATUS_PENDING = 'pending';

	private $id;

	/**
	 * Retrieve all products in Order.
	 *
	 * @param int $id
	 * @return object
	 */
	protected function getProducts() {
		$orderWc     = new \WC_Order( $this->id );
		$order_items = $orderWc->get_items();
		$products    = array();
		foreach ( $order_items as $product ) {
			$data       = $product->get_data();
			$products[] = (object) array(
				'id'           => $data['product_id'],
				'variation_id' => $data['variation_id'],
				'name'         => $data['name'],
				'quantity'     => $data['quantity'],
				'total'        => $data['total'],
			);
		}
		return $products;
	}
}
