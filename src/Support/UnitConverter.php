<?php

declare(strict_types=1);

namespace MelhorEnvio\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class UnitConverter {

	public static function toKg( float $weight ): float {
		$fromUnit = strtolower( (string) get_option( 'woocommerce_weight_unit', 'kg' ) );
		return (float) wc_get_weight( $weight, 'kg', $fromUnit );
	}

	public static function toCm( float $value ): float {
		$fromUnit = strtolower( (string) get_option( 'woocommerce_dimension_unit', 'cm' ) );
		return (float) wc_get_dimension( $value, 'cm', $fromUnit );
	}
}
