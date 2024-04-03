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
			case 'simple':
				return new \MelhorEnvio\Services\Products\ProductsService();
			default:
				throw new Exception('Invalid type');
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
