<?php

namespace MelhorEnvio\Factory;

use Exception;

class ProductFactory
{
	/**
	 * @throws Exception
	 */
	public static function createProductServiceByProduct($product)
	{
		switch ($product->get_type()) {
			case 'woosb':
				return new \MelhorEnvio\Services\Products\BundleService();
			case 'composite':
				return new \MelhorEnvio\Services\Products\CompositeService();
			default:
				return new \MelhorEnvio\Services\Products\ProductsService();
		}
	}

	/**
	 * @throws Exception
	 */
	public static function createProductServiceById($productId)
	{
		$product = wc_get_product($productId);

		return self::createProductServiceByProduct($product);
	}
}
