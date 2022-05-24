<?php

namespace MelhorEnvio\Services;

use MelhorEnvio\Helpers\DimensionsHelper;

class CartWooCommerceService {

	/**
	 * Function to get alls products on cart woocommerce
	 *
	 * @return Array
	 */
	public function getProducts() {
		global $woocommerce;

		$items = $woocommerce->cart->get_cart();

		$products = array();

		foreach ( $items as $itemProduct ) {
			$productId = ( $itemProduct['variation_id'] != 0 )
				? $itemProduct['variation_id']
				: $itemProduct['product_id'];

			$productInfo = wc_get_product( $productId );

			if ( empty( $productInfo ) ) {
				continue;
			}
			$data = $productInfo->get_data();

			$products[] = array(
				'id'              => $itemProduct['product_id'],
				'variation_id'    => $itemProduct['variation_id'],
				'name'            => $data['name'],
				'price'           => $productInfo->get_price(),
				'insurance_value' => $productInfo->get_price(),
				'height'          => DimensionsHelper::convertUnitDimensionToCentimeter( $productInfo->get_height() ),
				'width'           => DimensionsHelper::convertUnitDimensionToCentimeter( $productInfo->get_width() ),
				'length'          => DimensionsHelper::convertUnitDimensionToCentimeter( $productInfo->get_length() ),
				'weight'          => DimensionsHelper::convertWeightUnit( $productInfo->get_weight() ),
				'quantity'        => ( isset( $itemProduct['quantity'] ) )
					? intval( $itemProduct['quantity'] )
					: 1,
			);
		}
		return $products;
	}
}
