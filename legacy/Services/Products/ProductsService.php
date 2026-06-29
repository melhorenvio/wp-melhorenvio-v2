<?php

namespace MelhorEnvio\Services\Products;

use MelhorEnvio\Helpers\DimensionsHelper;
use MelhorEnvio\Models\Product;
use MelhorEnvio\Services\ConfigurationsService;

class ProductsService {

	public $product;

	public static function hasProductComposition($product): bool
	{
		return CompositeService::isCompositeProduct($product) || BundleService::isBundleProduct($product);
	}

	public static function removeComponentProducts($products): array
	{
		return array_filter($products, function ($product) {
			return !isset($product->parentId);
		});
	}

	/**
	 * @param int      $postId
	 * @param null|int $quantity
	 * @return object
	 */
	public function getProduct( int $postId, int $quantity = null ) {
		$product = wc_get_product( $postId );

		if ( empty( $quantity ) ) {
			$quantity = 1;
		}

		return $this->normalize( $product, $product->get_price(), $quantity );
	}

	/**
	 * Function to obtain the insurance value of one or more products.
	 *
	 * @param array|object $products
	 * @return float
	 */
	public function getInsuranceValue( $products, $valueBase = 0 ) {
		$insuranceValue = $valueBase;
		foreach ( $products as $product ) {
			$product = (object) $product;
			if ( ! empty( $product->unitary_value ) ) {
				$insuranceValue += $product->unitary_value * $product->quantity;
			}
		}

		if ( $insuranceValue == 0 ) {
			$insuranceValue = floatval( 1 );
		}

		return $insuranceValue;
	}

	/**
	 * function to remove the price field from
	 * the product to perform the quote without insurance value
	 *
	 * @param array $products
	 * @return array
	 */
	public function removePrice( $products ) {
		$response = array();
		foreach ( $products as $product ) {
			$response[] = (object) array(
				'id'            => $product->id,
				'name'          => $product->name,
				'quantity'      => $product->quantity,
				'unitary_value' => $product->unitary_value,
				'weight'        => $product->weight,
				'width'         => $product->width,
				'height'        => $product->height,
				'length'        => $product->length,
			);
		}

		return $response;
	}

	/**
	 * Function to filter products to api Melhor Envio.
	 *
	 * @param array $data
	 * @return array
	 */
	public function filter( $data ) {
		$products = array();
		foreach ( $data as $key => $item ) {
			/** @var Product $item */

			if ( ! empty( $item->shipping_fee ) && $item->shipping_fee == 'whole' ) {
				$item->components = [];
			}

			if ( ! empty( $item->shipping_fee ) && $item->shipping_fee == 'each' && ! empty( $item->components ) ) {
				foreach ($item->components as $component) {
					$products[] = $component;
				}
				continue;
			}

			if ( ! empty( $item->type ) && $item->type == InvalidProduct::INVALID_TYPE ) {
				$products[] = $item;
				continue;
			}

			if ( ! empty( $item->name ) && ! empty( $item->id ) ) {
				$products[$key] = $item;
				continue;
			}

			if ( ! empty( $item['name'] ) && ! empty( $item['id'] ) ) {
				$products[$key] = (object) $item;
				continue;
			}

			$product    = $item['data'];
			$products[$key] = $this->normalize(
				$product,
				$product->get_price(),
				$item['quantity']
			);
		}

		return $products;
	}

	public function getDataByProductCart( $productCart , $items): Product
	{
		$data = self::normalize(
			$productCart['data'],
			$productCart['data']->get_price(),
			$productCart['quantity']
		);

		if (isset($productCart['wooco_parent_id'])){
			$data->parentId = $productCart['wooco_parent_id'];
		}

		if (isset($productCart['woosb_parent_id'])){
			$data->parentId = $productCart['woosb_parent_id'];
		}

		return $data;
	}

	public function getDataByProductOrder( $productOrder, $items)
	{
		$data = self::normalize(
			$productOrder->get_product(),
			$productOrder->get_product()->get_price(),
			$productOrder->get_quantity()
		);

		if (! empty( $productOrder->get_meta('wooco_parent_id', true) )){
			$data->parentId = $productOrder->get_meta('wooco_parent_id', true);
		}

		if (! empty( $productOrder->get_meta('_woosb_parent_id', true) )){
			$data->parentId = $productOrder->get_meta('_woosb_parent_id', true);
		}

		return $data;
	}
	/**
	 * @param $product
	 * @param $price
	 * @param int $quantity
	 * @return Product
	 */
	public function normalize($product, $price, $quantity = 1): Product
	{
		$this->setDimensions( $product );

		$data = new Product();

		$data->id = $product->get_id();
		$data->name = $product->get_name();
		$data->width = DimensionsHelper::convertUnitDimensionToCentimeter( $product->get_width() );
		$data->height = DimensionsHelper::convertUnitDimensionToCentimeter( $product->get_height() );
		$data->length = DimensionsHelper::convertUnitDimensionToCentimeter( $product->get_length() );
		$data->weight = DimensionsHelper::convertWeightUnit( $product->get_weight() );
		$data->quantity = $quantity;
		$data->type = $product->get_type();
		$data->is_virtual = $product->get_virtual();

		$data->setValues($price);

		return $data;
	}

	/**
	 * function to check if prouct has all dimensions.
	 *
	 * @param object $product
	 */
	private function setDimensions( $product ) {
		$dimensionDefault = ( new ConfigurationsService() )->getDimensionDefault();

		if ( empty( $product->get_width() ) ) {
			$product->set_width( $dimensionDefault['width'] );
		}

		if ( empty( $product->get_height() ) ) {
			$product->set_height( $dimensionDefault['height'] );
		}

		if ( empty( $product->get_length() ) ) {
			$product->set_length( $dimensionDefault['length'] );
		}

		if ( empty( $product->get_weight() ) ) {
			$product->set_weight( $dimensionDefault['weight'] );
		}
	}
}
