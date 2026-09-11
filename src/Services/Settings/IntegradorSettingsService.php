<?php

declare(strict_types=1);

namespace MelhorEnvio\Services\Settings;

final class IntegradorSettingsService {

	private const OPTION_KEY                    = 'melhor_envio_integrador_settings';
	private const LEGACY_HIDE_CALCULATOR        = 'melhorenvio_hide_calculator_product';
	private const LEGACY_CALCULATOR_POSITION    = 'melhor_envio_option_where_show_calculator';
	private const LEGACY_DIMENSION_DEFAULT      = 'melhor_envio_option_dimension_default';
	private const DEFAULT_CALCULATOR_POSITION   = 'woocommerce_before_add_to_cart_button';
	private const DEFAULT_DIMENSION_HEIGHT      = 10;
	private const DEFAULT_DIMENSION_WIDTH       = 10;
	private const DEFAULT_DIMENSION_LENGTH      = 10;
	private const DEFAULT_DIMENSION_WEIGHT      = 11;
	private const DEFAULT_DOCUMENT_TYPE         = 'both';
	private const DEFAULT_REQUIRE_NUMBER        = true;
	private const DEFAULT_REQUIRE_NEIGHBORHOOD  = true;

	public function getSettings(): array {
		$stored = get_option( self::OPTION_KEY );

		if ( is_array( $stored ) && ! empty( $stored ) ) {
			return $stored;
		}

		$legacy = $this->getLegacySettings();

		if ( ! empty( $legacy ) ) {
			return $legacy;
		}

		return $this->getDefaults();
	}

	public function saveSettings( array $settings ): bool {
		$updated = update_option( self::OPTION_KEY, $settings );

		// update_option returns false both on failure and when value is unchanged.
		// If unchanged, consider it a success.
		if ( ! $updated && get_option( self::OPTION_KEY ) === $settings ) {
			return true;
		}

		return (bool) $updated;
	}

	private function getLegacySettings(): array {
		$hideCalculator = get_option( self::LEGACY_HIDE_CALCULATOR );
		$position       = get_option( self::LEGACY_CALCULATOR_POSITION );
		$dimensions     = get_option( self::LEGACY_DIMENSION_DEFAULT );

		if ( $hideCalculator === false && $position === false && $dimensions === false ) {
			return array();
		}

		$defaults = $this->getDefaults();

		$calculatorEnabled = ! ( $hideCalculator === '1' || $hideCalculator === 1 || $hideCalculator === true );

		$calculatorPosition = ( is_string( $position ) && ! empty( $position ) )
			? $position
			: self::DEFAULT_CALCULATOR_POSITION;

		$dimensionsDefault = $defaults['dimensions_default'];
		if ( is_array( $dimensions ) && ! empty( $dimensions ) ) {
			$dimensionsDefault = array(
				'height' => isset( $dimensions['height'] ) ? (float) $dimensions['height'] : self::DEFAULT_DIMENSION_HEIGHT,
				'width'  => isset( $dimensions['width'] ) ? (float) $dimensions['width'] : self::DEFAULT_DIMENSION_WIDTH,
				'length' => isset( $dimensions['length'] ) ? (float) $dimensions['length'] : self::DEFAULT_DIMENSION_LENGTH,
				'weight' => isset( $dimensions['weight'] ) ? (float) $dimensions['weight'] : self::DEFAULT_DIMENSION_WEIGHT,
			);
		}

		return array(
			'calculator'        => array(
				'enabled'  => $calculatorEnabled,
				'position' => $calculatorPosition,
			),
			'dimensions_default' => $dimensionsDefault,
		);
	}

	private function getDefaults(): array {
		return array(
			'calculator'        => array(
				'enabled'  => true,
				'position' => self::DEFAULT_CALCULATOR_POSITION,
			),
			'dimensions_default' => array(
				'height' => self::DEFAULT_DIMENSION_HEIGHT,
				'width'  => self::DEFAULT_DIMENSION_WIDTH,
				'length' => self::DEFAULT_DIMENSION_LENGTH,
				'weight' => self::DEFAULT_DIMENSION_WEIGHT,
			),
			'checkout' => array(
				'document_type'       => self::DEFAULT_DOCUMENT_TYPE,
				'require_number'      => self::DEFAULT_REQUIRE_NUMBER,
				'require_neighborhood' => self::DEFAULT_REQUIRE_NEIGHBORHOOD,
			),
		);
	}

	public function getCheckoutSettings(): array {
		$settings = $this->getSettings();
		$defaults = $this->getDefaults()['checkout'];
		$checkout = $settings['checkout'] ?? array();

		return array(
			'document_type'        => $checkout['document_type'] ?? $defaults['document_type'],
			'require_number'       => $checkout['require_number'] ?? $defaults['require_number'],
			'require_neighborhood' => $checkout['require_neighborhood'] ?? $defaults['require_neighborhood'],
		);
	}
}
