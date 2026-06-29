<?php

namespace MelhorEnvio\Services;

use MelhorEnvio\Factory\ProductServiceFactory;
use MelhorEnvio\Services\Products\ProductsService;

class OrdersProductsService {

	/**
	 * Get products by order
	 *
	 * @param int $orderId
	 * @return array $products
	 */
	public function getProductsOrder( $orderId ) {
		$order = wc_get_order( $orderId );

		$items = $order->get_items();

		$products = array_map(function($item) use ($items) {
			$productService = ProductServiceFactory::fromProduct($item->get_product());

			return $productService->getDataByProductOrder( $item, $items );
		}, $items);

		return ProductsService::removeComponentProducts($products);
	}
}
