<?php

namespace MelhorEnvio\Services;

use MelhorEnvio\Helpers\DimensionsHelper;
use MelhorEnvio\Services\WooCommerceBundleProductsService;
use MelhorEnvio\Services\ProductsService;

class OrdersProductsService {

	/**
	 * Get products by order
	 *
	 * @param int $orderId
	 * @return array $products
	 */
	public function getProductsOrder( $orderId ) {
		$order = wc_get_order( $orderId );

		$products = array();

		$productsComposite = array();

		$productService = new ProductsService();

		$products = array();

		$quantities = array();

		$productsIgnoreBundle = array();

		$wooCommerceBundleProductService = new WooCommerceBundleProductsService();

		foreach ( $order->get_items() as $key => $itemProduct ) {
			$metas = $wooCommerceBundleProductService->getMetas( $itemProduct );

			if ( $wooCommerceBundleProductService->isBundledItem( $metas ) ) {
				$bundleType = $wooCommerceBundleProductService->getBundledItemType( $metas );
				if ( $bundleType == WooCommerceBundleProductsService::BUNDLE_TYPE_INTERNAL ) {
					$products = $wooCommerceBundleProductService->getInternalProducts(
						$itemProduct->get_data(),
						$metas,
						$products
					);
					continue;
				}

				if ( $bundleType == WooCommerceBundleProductsService::BUNDLE_TYPE_EXTERNAL ) {
					$productsInBundle = $wooCommerceBundleProductService->getProducts( $metas['_stamp'] );
					foreach ( $productsInBundle as $prod ) {
						$productsIgnoreBundle[] = $prod->id;
					}
					$product                  = $wooCommerceBundleProductService->getExternalProducts(
						$itemProduct->get_data(),
						$metas
					);
					$quantities               = $this->incrementQuantity( $product->id, $quantities, $product->quantity );
					$products[ $product->id ] = $product;
					continue;
				}
			}

			$product = $itemProduct->get_product();
			if ( is_bool( $product ) || get_class( $product ) === CompositeProductBundleService::PRODUCT_COMPOSITE ) {
				$compositeBundleService = new CompositeProductBundleService( $itemProduct );
				$productComposite       = $compositeBundleService->getProductNormalize();

				if ( empty( $productComposite ) ) {
					continue;
				}
				$productsComposite[ $key ] = $productComposite;
			}

			if ( ! in_array( $product->get_id(), $productsIgnoreBundle ) ) {
				$productId = $product->get_id();
				$quantity  = $itemProduct->get_quantity();

				$products[ $productId ] = $productService->normalize(
					$product,
					$itemProduct->get_quantity()
				);

				$quantities = $this->incrementQuantity(
					$productId,
					$quantities,
					$quantity
				);
			}
		}

		if ( isset( $compositeBundleService ) ) {
			return $compositeBundleService->selectProductsToReturnByTypeComposite(
				$productsComposite,
				$products
			);
		}

		foreach ( $products as $key => $product ) {
			if ( ! empty( $quantities[ $product->id ] ) ) {
				$products[ $key ]->quantity = $quantities[ $product->id ];
			}
		}
		return $products;
	}

	/**
	 * @param int   $productId
	 * @param array $quantities
	 * @param int   $quantity
	 * @return array
	 */
	public function incrementQuantity( $productId, $quantities, $quantity ) {
		$actualQuantity = ( ! empty( $quantities[ $productId ] ) ) ? $quantities[ $productId ] : 0;

		$quantities[ $productId ] = $actualQuantity + $quantity;

		return $quantities;
	}
}
