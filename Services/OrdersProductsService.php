<?php

namespace MelhorEnvio\Services;

use MelhorEnvio\Factory\ProductFactory;

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

		foreach ($items as $itemProduct) {
			$productService = ProductFactory::createProductServiceByProduct($itemProduct->get_product());

			$products[] = $productService->getDataByProductOrder( $itemProduct, $items );
		}

		foreach ($products as $key => $product) {
			if (!empty($product->parentId)){
				unset($products[$key]);
			}
		}

		return $products;
	}
}
