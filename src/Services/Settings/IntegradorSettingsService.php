<?php

declare(strict_types=1);

namespace MelhorEnvio\Services\Settings;

final class IntegradorSettingsService {

	private const OPTION_KEY                 = 'melhor_envio_integrador_settings';
	private const LEGACY_HIDE_CALCULATOR     = 'melhorenvio_hide_calculator_product';
	private const LEGACY_CALCULATOR_POSITION = 'melhor_envio_option_where_show_calculator';
	private const LEGACY_DIMENSION_DEFAULT   = 'melhor_envio_option_dimension_default';

	public function getSettings(): array {
		$stored = get_option( self::OPTION_KEY );

		if ( is_array( $stored ) && ! empty( $stored ) ) {
			return $stored;
		}

		return $this->getLegacySettings();
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

	public function getCheckoutSettings(): array {
		$settings = $this->getSettings();

		return $settings['checkout'] ?? array();
	}

	private function getLegacySettings(): array {
		$hideCalculator = get_option( self::LEGACY_HIDE_CALCULATOR );
		$position       = get_option( self::LEGACY_CALCULATOR_POSITION );
		$dimensions     = get_option( self::LEGACY_DIMENSION_DEFAULT );

		if ( $hideCalculator === false && $position === false && $dimensions === false ) {
			return array();
		}

		$settings = array();

		$settings['calculator'] = array(
			'enabled' => ! ( $hideCalculator === '1' || $hideCalculator === 1 || $hideCalculator === true ),
		);

		if ( is_string( $position ) && ! empty( $position ) ) {
			$settings['calculator']['position'] = $position;
		}

		if ( is_array( $dimensions ) && ! empty( $dimensions ) ) {
			$dim = array();

			if ( isset( $dimensions['height'] ) ) {
				$dim['height'] = (float) $dimensions['height'];
			}

			if ( isset( $dimensions['width'] ) ) {
				$dim['width'] = (float) $dimensions['width'];
			}

			if ( isset( $dimensions['length'] ) ) {
				$dim['length'] = (float) $dimensions['length'];
			}

			if ( isset( $dimensions['weight'] ) ) {
				$dim['weight'] = (float) $dimensions['weight'];
			}

			$settings['dimensions_default'] = $dim;
		}

		return $settings;
	}
}
