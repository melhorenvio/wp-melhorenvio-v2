<?php

namespace MelhorEnvio\Services;

use MelhorEnvio\Helpers\DimensionsHelper;
use MelhorEnvio\Services\ProductsService;

class WooCommerceBundleProductsService {

	const OBJECT_WOOCOMMERCE_BUNDLE = 'WC_Product_Bundle';

	const OBJECT_PRODUCT_SIMPLE = 'WC_Product_Simple';

	const TYPE_LAYOUT_BUNDLE_DEFAULT = 'default';

	const BUNDLE_TYPE_EXTERNAL = 'external';

	const BUNDLE_TYPE_INTERNAL = 'internal';

	/**
	 * Function to check if a order is Bundle Product Class.
	 *
	 * @param array $data
	 * @return bool
	 */
	public static function isWooCommerceProductBundle( $data ) {
		$item = end( $data );
		return ( ( ! empty( $item['bundled_by'] ) || ! empty( $item['bundled_items'] ) ) && ! empty( $item['stamp'] ) );
	}

	/**
	 * Function to manage products by bundle
	 *
	 * @param array $items
	 * @return array
	 */
	public function manageProductsBundle( $items ) {
		$products       = array();
		$productService = new ProductsService();

		foreach ( $items as $key => $data ) {
			if ( $this->shouldUseProducts( $data ) ) {
				if ( isset( $data['bundled_by'] ) ) {
					foreach ( $data['stamp'] as $product ) {
						$products[ $product['product_id'] ] = $productService->getProduct(
							$product['product_id'],
							$items[ $key ]['quantity']
						);
					}
					continue;
				}
			}

			if ( $this->shoudUsePackage( $data ) ) {
				$productId = $data['data']->get_id();
				$weight    = 0;
				if ( $data['data']->get_aggregate_weight() ) {
					foreach ( $data['stamp'] as $product ) {
						$internalProduct = $productService->getProduct(
							$product['product_id'],
							$data['quantity']
						);
						$weight          = $weight + (float) $internalProduct->weight;
					}
				}

				$internalProduct         = $productService->getProduct( $productId, $data['quantity'] );
				$internalProduct->weight = (float) $internalProduct->weight + $weight;
				$products[ $productId ]  = $internalProduct;
				continue;
			}

			$products[] = $productService->getProduct(
				$data['product_id'],
				$data['quantity']
			);
		}

		return $products;
	}

	/**
	 * @param array $data
	 * @return bool
	 */
	private function shouldUseProducts( $data ) {
		return isset( $data['bundled_by'] );
	}

	/**
	 * @param array $data
	 * @return bool
	 */
	private function shoudUsePackage( $data ) {
		return isset( $data['bundled_items'] );
	}

	/**
	 * @param object $data
	 * @return bool
	 */
	private function isVirtualBundle( $data ) {
		if ( ! isset( $data->virtual ) ) {
			return false;
		}

		return $data->virtual == 'yes';
	}

	/**
	 * @param array $iemOrder
	 * @return array
	 */
	public function getMetas( $itemOrder ) {
		$metas = array();
		foreach ( $itemOrder->get_meta_data() as $key => $item ) {
			$data                  = $item->get_data();
			$metas[ $data['key'] ] = $data['value'];
		}

		if ( empty( $metas['_bundled_items'] ) ) {
			return array();
		}

		return $metas;
	}

	/**
	 * @param array $meta
	 * @return bool
	 */
	public function isBundledItem( $metas ) {
		return ! empty( $metas );
	}

	/**
	 * @param array $meta
	 * @return string
	 */
	public function getBundledItemType( $metas ) {
		if ( ! empty( $metas['_bundle_weight'] ) ) {
			return self::BUNDLE_TYPE_EXTERNAL;
		}

		return self::BUNDLE_TYPE_INTERNAL;
	}

	/**
	 * @param array $stamp
	 * @return array
	 */
	public function getProducts( $stamp ) {
		$productService = new ProductsService();

		$products = array();
		foreach ( $stamp as $product ) {
			$products[ $product['product_id'] ] = $productService->getProduct(
				$product['product_id'],
				$product['quantity']
			);
		}

		return $products;
	}

	/**
	 * @param array $product
	 * @param array $metas
	 * @param array $products
	 * @return array
	 */
	public function getInternalProducts( $product, $metas, $products ) {
		if ( empty( $metas['_stamp'] ) ) {
			return false;
		}
		$productsBundle = $this->getProducts( $metas['_stamp'] );

		if ( empty( $productsBundle ) ) {
			return $products;
		}

		if ( empty( $products ) ) {
			return $productsBundle;
		}

		return $products;
	}

	/**
	 * @param array $product
	 * @param array $metas
	 * @return object
	 */
	public function getExternalProducts( $product, $metas ) {
		$productService = new ProductsService();

		$product = $productService->getProduct(
			$product['product_id'],
			$product['quantity']
		);

		$product->weight = $metas['_bundle_weight'];

		return $product;
	}
}
