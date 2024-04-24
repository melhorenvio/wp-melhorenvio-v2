<?php

namespace MelhorEnvio\Services\Products;

use MelhorEnvio\Models\Product;

class BundleService extends ProductsService
{
	const PRODUCT_BUNDLE_TYPE = 'woosb';
	const PRODUCT_BUNDLE_SHIPPING_FEE = 'woosb_shipping_fee';

	public function getDataByProductCart( $productCart , $items): Product
	{
		$data = parent::normalize(
			$productCart['data'],
			! empty($productCart['woosb_price']) ? $productCart['woosb_price'] : $productCart['line_total'],
			$productCart['quantity']
		);

		$data->shipping_fee = self::getShippingFeeType($productCart['data']->get_id());

		if ($data->type == self::PRODUCT_BUNDLE_TYPE) {
			if (isset($productCart['woosb_keys'])) {
				foreach ($productCart['woosb_keys'] as $key) {
					$data->components[] = parent::normalize(
						$items[$key]['data'],
						$items[$key]['line_total'] / $items[$key]['quantity'],
						$items[$key]['quantity']);
				}

				if($productCart['data']->is_fixed_price()) {
					$data->components[0]->setValues(($data->unitary_value/$data->components[0]->quantity));
				}
			}
		}

		return $data;
	}

	public function getDataByProductOrder( $productOrder, $items ): Product
	{
		$value = ! empty($productOrder->get_meta('_woosb_price', true)) ?
			$productOrder->get_meta('_woosb_price', true) :
			$productOrder->get_total();

		$data = parent::normalize(
			$productOrder->get_product(),
			$value,
			$productOrder->get_quantity()
		);

		$data->shipping_fee = self::getShippingFeeType($productOrder->get_product()->get_id());

		if ($data->type == self::PRODUCT_BUNDLE_TYPE) {
			$components = (new \WPCleverWoosb())->get_bundled($productOrder->get_meta('_woosb_ids', true));

			foreach ($components as $component) {
				foreach ($items as $item) {
					if ($item->get_product_id() == $component['id']) {
						$product = $item->get_product();
						$quantity = $item->get_quantity();
						$total = $item->get_total();
					}
				}

				$data->components[] = parent::normalize(
					$product ?? wc_get_product($component['id']),
					$total ?? wc_get_product($component['id'])->get_price(),
					$quantity ?? $component['qty']
				);
			}

			if($productOrder->get_product()->is_fixed_price()) {
				$data->components[0]->setValues(($data->unitary_value/$data->components[0]->quantity));
			}
		}

		return $data;
	}

	/**
	 * Function to get type shipping fee
	 *
	 * @param $productId
	 * @return string
	 */
	public function getShippingFeeType( $productId ): string
	{
		return get_post_meta( $productId, self::PRODUCT_BUNDLE_SHIPPING_FEE, true );
	}
}
