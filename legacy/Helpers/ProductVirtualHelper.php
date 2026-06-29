<?php

namespace MelhorEnvio\Helpers;

class ProductVirtualHelper {

	/**
	 * @param array $products
	 * @return array
	 */
	public static function removeVirtuals( $products ) {
		foreach ( $products as $key => $product ) {
			if ( $product->is_virtual ) {
				unset( $products[ $key ] );
			}
		}
		return $products;
	}
}
