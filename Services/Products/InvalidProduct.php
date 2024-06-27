<?php

namespace MelhorEnvio\Services\Products;

use MelhorEnvio\Models\Product;
use MelhorEnvio\Services\ConfigurationsService;

class InvalidProduct extends ProductsService
{
	const INVALID_TYPE = 'invalid';

	public function getDataByProductOrder( $productOrder, $items): Product
	{
		return $this->normalize(
			$productOrder,
			$productOrder->get_total()/ $productOrder->get_quantity(),
			$productOrder->get_quantity()
		);
	}

	public function normalize($product, $price, $quantity = 1): Product
	{
		$data = new Product();

		$data->id = $product->get_product_id();
		$data->name = $product->get_name();
		$data->quantity = $quantity;
		$data->type = "invalid";
		$data->is_virtual = false;

		$dimensionDefault = ( new ConfigurationsService() )->getDimensionDefault();

		$data->width = $dimensionDefault['width'];
		$data->height = $dimensionDefault['height'];
		$data->length = $dimensionDefault['length'];
		$data->weight = $dimensionDefault['weight'];

		$data->setValues($price);

		return $data;
	}
}
