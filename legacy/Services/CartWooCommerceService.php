<?php

namespace MelhorEnvio\Services;

use Exception;
use MelhorEnvio\Factory\ProductServiceFactory;
use MelhorEnvio\Helpers\DimensionsHelper;
use MelhorEnvio\Services\Products\ProductsService;

class CartWooCommerceService {

	/**
	 * Function to get alls products on cart woocommerce
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getProducts(): array
	{
		global $woocommerce;

		$items = $woocommerce->cart->get_cart();

		$products = array_map(function($item) use ($items) {
			$productId = ( $item['variation_id'] != 0 )
				? $item['variation_id']
				: $item['product_id'];

			$productService = ProductServiceFactory::fromId($productId);

			return $productService->getDataByProductCart($item, $items);
		}, $items);

		return ProductsService::removeComponentProducts($products);
	}
}
