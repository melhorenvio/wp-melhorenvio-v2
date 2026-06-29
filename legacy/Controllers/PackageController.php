<?php

namespace MelhorEnvio\Controllers;

use MelhorEnvio\Helpers\DimensionsHelper;

class PackageController {

	const GRAMS = 'g';
	/**
	 * Function to assemble the standard package for woocommerce
	 *
	 * @param array $package
	 * @return array
	 */
	public function getPackage( $package ) {
		$weight = 0;
		$width  = 0;
		$height = 0;
		$length = 0;

		foreach ( $package['contents'] as $values ) {
			$product = $values['data'];
			$weight  = $weight + $product->get_weight() * $values['quantity'];

			$width  += $product->width;
			$height += $product->height;
			$length += $product->length;
		}

		return $this->convertWeightUnit(
			array(
				'weight' => $weight,
				'width'  => $width,
				'height' => $height,
				'length' => $length,
			)
		);
	}

	/**
	 * Function to fetch an order package
	 *
	 * @param int $orderId
	 * @return array
	 */
	public function getPackageOrder( $orderId ) {
		$weight = 0;
		$width  = 0;
		$height = 0;
		$length = 0;
		$order  = wc_get_order( $orderId );

		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();

			$weight += $product->weight * $item->get_quantity();
			$width  += $product->width;
			$height += $product->height;
			$length += $product->length;
		}

		return $this->convertWeightUnit(
			array(
				'weight' => $weight,
				'width'  => $width,
				'height' => $height,
				'length' => $length,
			)
		);
	}

	/**
	 * Function to convert the measurements of a package to the Melhor Envio standard
	 *
	 * @param array $package
	 * @return array
	 */
	private function convertWeightUnit( $package ) {
		$weightUnit = get_option( 'woocommerce_weight_unit' );
		if ( $weightUnit == self::GRAMS ) {
			$package['weight'] = $package['weight'] / 1000;
		}

		$package['width']  = DimensionsHelper::convertUnitDimensionToCentimeter( $package['width'] );
		$package['height'] = DimensionsHelper::convertUnitDimensionToCentimeter( $package['height'] );
		$package['length'] = DimensionsHelper::convertUnitDimensionToCentimeter( $package['length'] );

		return $package;
	}
}
