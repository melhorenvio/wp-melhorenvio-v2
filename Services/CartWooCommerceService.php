<?php

namespace MelhorEnvio\Services;

use Exception;
use MelhorEnvio\Factory\ProductFactory;
use MelhorEnvio\Helpers\DimensionsHelper;

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

		$products = array();

		foreach ( $items as $itemProduct ) {
			$productId = ( $itemProduct['variation_id'] != 0 )
				? $itemProduct['variation_id']
				: $itemProduct['product_id'];

			$productService = ProductFactory::createProductServiceById($productId);

			$products[] = $productService->getDataByProductCart($itemProduct, $items);
		}

		foreach ($products as $key => $product) {
			if (isset($product->parentId)){
				unset($products[$key]);
			}
		}

		return $products;
	}
}
