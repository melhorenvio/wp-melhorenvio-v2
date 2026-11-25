<?php

declare(strict_types=1);

namespace MelhorEnvio\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sem o WooCommerce carregado (ex.: desativado), devolve o valor como está em vez de derrubar o site:
 * nesse estado não há cotação, e getSettings() roda no boot do plugin.
 */
final class UnitConverter {

	public static function toKg( float $weight ): float {
		if ( ! function_exists( 'wc_get_weight' ) ) {
			return $weight;
		}

		$fromUnit = strtolower( (string) get_option( 'woocommerce_weight_unit', 'kg' ) );
		return (float) wc_get_weight( $weight, 'kg', $fromUnit );
	}

	public static function toCm( float $value ): float {
		if ( ! function_exists( 'wc_get_dimension' ) ) {
			return $value;
		}

		$fromUnit = strtolower( (string) get_option( 'woocommerce_dimension_unit', 'cm' ) );
		return (float) wc_get_dimension( $value, 'cm', $fromUnit );
	}
}
