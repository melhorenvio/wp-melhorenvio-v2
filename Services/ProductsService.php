<?php

namespace MelhorEnvio\Services;

use MelhorEnvio\Helpers\DimensionsHelper;
use MelhorEnvio\Services\WooCommerceBundleProductsService;
use MelhorEnvio\Services\SessionNoticeService;

class ProductsService {

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

		return $this->normalize( $product, $quantity );
	}

	/**
	 * Function to obtain the insurance value of one or more products.
	 *
	 * @param array|object $products
	 * @return float
	 */
	public function getInsuranceValue( $products ) {
		$insuranceValue = 0;
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
	 * @param array $products
	 * @return array
	 */
	public function filter( $data ) {
		$products = array();
		foreach ( $data as $item ) {
			if ( $this->isObjectProduct( $item ) ) {
				$data       = $item->get_data();
				$product    = $item;
				$products[] = $this->normalize( $product, $item['quantity'] );
				continue;
			}

			if ( ! empty( $item->name ) && ! empty( $item->id ) ) {
				$products[] = $item;
				continue;
			}

			$product    = $item['data'];
			$products[] = $this->normalize( $product, $item['quantity'] );
		}

		return $products;
	}

	/**
	 * @param object $product
	 * @return bool
	 */
	private function isObjectProduct( $item ) {
		return (
			! is_array( $item ) &&
			(
				get_class( $item ) == WooCommerceBundleProductsService::OBJECT_PRODUCT_SIMPLE ||
				get_class( $item ) == WooCommerceBundleProductsService::OBJECT_WOOCOMMERCE_BUNDLE
			)
		);
	}

	/**
	 * @param WC_Product_Simple $product
	 * @param int               $quantity
	 * @return object
	 */
	public function normalize( $product, $quantity = 1 ) {
		$price = floatval( $product->get_price() );
		if ( empty( $price ) ) {
			$data = $product->get_data();
			if ( isset( $data['price'] ) ) {
				$price = floatval( $data['price'] );
			}
		}

		$this->setDimensions( $product );

		return (object) array(
			'id'              => $product->get_id(),
			'name'            => $product->get_name(),
			'width'           => DimensionsHelper::convertUnitDimensionToCentimeter( $product->get_width() ),
			'height'          => DimensionsHelper::convertUnitDimensionToCentimeter( $product->get_height() ),
			'length'          => DimensionsHelper::convertUnitDimensionToCentimeter( $product->get_length() ),
			'weight'          => DimensionsHelper::convertWeightUnit( $product->get_weight() ),
			'unitary_value'   => $price,
			'insurance_value' => $price,
			'quantity'        => $quantity,
			'class'           => get_class( $product ),
			'is_virtual'      => $product->get_virtual(),
		);
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

	/**
	 * function to return a label with the name of products.
	 *
	 * @param array $products
	 * @return string
	 */
	public function createLabelTitleProducts( $products ) {
		$title = '';
		foreach ( $products as $id => $product ) {
			if ( ! empty( $product['data']->get_name() ) ) {
				$title = $title . sprintf(
					"<a href='%s'>%s</a>, ",
					get_edit_post_link( $id ),
					$product['data']->get_name()
				);
			}
		}

		if ( ! empty( $title ) ) {
			$title = substr( $title, 0, -2 );
		}

		return 'Produto(s): ' . $title;
	}
}
