<?php

namespace MelhorEnvio\Services;

use MelhorEnvio\Helpers\DimensionsHelper;

class PackageService {

	/**
	 * Get package of quotation.
	 *
	 * @param array $quotation
	 * @return array $packages
	 */
	public function getPackageQuotation( $quotation ) {
		$response = null;

		if ( empty( $quotation ) || is_null( $quotation ) ) {
			return $response;
		}
		foreach ( $quotation as $item ) {
			if ( ! isset( $item->id ) || is_null( $item->id ) ) {
				continue;
			}

			if ( isset( $item->packages ) ) {
				foreach ( $item->packages as $package ) {
					$response[ $item->id ] = (object) array(
						'largura'     => DimensionsHelper::convertUnitDimensionToCentimeter(
							$package->dimensions->width
						),
						'altura'      => DimensionsHelper::convertUnitDimensionToCentimeter(
							$package->dimensions->height
						),
						'comprimento' => DimensionsHelper::convertUnitDimensionToCentimeter(
							$package->dimensions->length
						),
						'peso'        => DimensionsHelper::convertWeightUnit( $package->weight ),
					);
				}
			} elseif ( isset( $item->volumes ) ) {
				foreach ( $item->volumes as $volume ) {
					$response[ $item->id ] = (object) array(
						'largura'     => DimensionsHelper::convertUnitDimensionToCentimeter( $volume->width ),
						'altura'      => DimensionsHelper::convertUnitDimensionToCentimeter( $volume->height ),
						'comprimento' => DimensionsHelper::convertUnitDimensionToCentimeter( $volume->length ),
						'peso'        => DimensionsHelper::convertWeightUnit( $volume->weight ),
					);
				}
			}
			continue;
		}
		return $response;
	}
}
