<?php

namespace MelhorEnvio\Factory;

use Exception;

class ProductServiceFactory
{
	/**
	 * @throws Exception
	 */
	public static function fromProduct($product)
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
	public static function fromId($productId)
	{
		$product = wc_get_product($productId);

		return self::fromProduct($product);
	}
}
