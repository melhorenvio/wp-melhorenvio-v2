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

		$orderProducts = $order->get_items();

		$productsComposite = $this->getProductsComposite($orderProducts);

		$productsBundle = $this->getProductsBundle($orderProducts);

		$productsSimple = $this->getProductsSimple($orderProducts);

		$productsSimple = $this->removeComponentsByComposite($productsComposite, $productsSimple);

		$productsSimple = $this->removeComponentsByBundle($productsBundle, $productsSimple);

		return array_merge($productsComposite, $productsBundle, $productsSimple);
	}

	public function removeComponentsByComposite($productsComposite, $productsSimple)
	{
		foreach ($productsComposite as $productComposite) {
			$components = wc_get_product($productComposite['id'])->get_components();

			$componentsQuantity = array_map(function($component) use ($productComposite) {
				return array_map(function($idProduct) use ($component, $productComposite) {
					return ["id" => $idProduct, "quantity" => ($component['qty'] * $productComposite['quantity'])];
				}, $component['products']);
			}, $components);

			foreach ($componentsQuantity as $componentQuantity) {
				foreach ($componentQuantity as $key => $product) {
					$productsSimple = $this->removeProduct($product, $productsSimple, "composite");
					$componentQuantity[$key]['quantity']--;
				}
			}
		}

		return $productsSimple;
	}

	public function removeComponentsByBundle($productsBundle, $productsSimple)
	{
		foreach ($productsBundle as $productBundle) {
			$components = wc_get_product($productBundle['id'])->get_items();

			$componentsQuantity = array_map(function($component) use ($productBundle) {
				return ["id" => $component['id'], "quantity" => ($component['qty'] * $productBundle['quantity'])];
			}, $components);

			foreach ($componentsQuantity as $componentQuantity) {
				$productsSimple = $this->removeProduct($componentQuantity, $productsSimple, "bundle");
				$componentQuantity['quantity']--;
			}
		}

		return $productsSimple;
	}

	public function getProductsComposite($orderProducts): array
	{
		$productsComposite = array();
		foreach ($orderProducts as $key => $itemProduct) {
			$product = $itemProduct->get_product();
			if ($this->isCompositeProduct($product)) {
				$compositeBundleService = new CompositeProductBundleService( $itemProduct );
				$productComposite = $compositeBundleService->getProductNormalize();
				if ( empty( $productComposite ) ) {
					continue;
				}
				$productsComposite[ $key ] = $productComposite;
			}
		}

		return $productsComposite;
	}

	public function getProductsBundle($orderProducts): array
	{
		$productsBundle = array();
		foreach ($orderProducts as $key => $itemProduct) {
			$product = $itemProduct->get_product();
			if ($this->isBundleProduct($product)) {
				$compositeBundleService = new CompositeProductBundleService( $itemProduct );
				$productBundle = $compositeBundleService->getProductNormalize();
				if ( empty( $productBundle ) ) {
					continue;
				}
				$productsBundle[ $key ] = $productBundle;
			}
		}

		return $productsBundle;
	}

	public function getProductsSimple($orderProducts): array
	{
		$productsSimple = array();
		foreach ($orderProducts as $key => $itemProduct) {
			$product = $itemProduct->get_product();
			if ($this->isSimpleProduct($product)) {
				$productService = new ProductsService();
				$productsSimple[ $key ] = $productService->normalize(
					$product,
					$itemProduct->get_quantity()
				);;
			}
		}

		return $productsSimple;
	}

	public function isCompositeProduct($product): bool
	{
		return is_bool($product) ||
			get_class($product) === CompositeProductBundleService::PRODUCT_COMPOSITE ||
			get_class($product) === CompositeProductBundleService::PRODUCT_COMBO_OFFICER;
	}

	public function isBundleProduct($product): bool
	{
		return is_bool($product) ||
			get_class($product) === CompositeProductBundleService::PRODUCT_BUNDLE;
	}

	public function isSimpleProduct($product): bool
	{
		return is_bool($product) ||
			get_class($product) === CompositeProductBundleService::PRODUCT_SIMPLE;
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

	public function removeProduct($product, $productsSimple, $type)
	{
		foreach ($productsSimple as $key => $productSimple) {
			if ($productSimple->id == $product['id'] && $product['quantity'] > 0) {

				if($productSimple->quantity > $product['quantity']) {
					$productsSimple[$key]->quantity -= $product['quantity'];
					$product['quantity']--;
					continue;
				}else if ($productSimple->quantity < $product['quantity']) {
					continue;
				}

				$product['quantity'] = 0;
				unset($productsSimple[$key]);
			}
		}

		return $productsSimple;
	}
}
