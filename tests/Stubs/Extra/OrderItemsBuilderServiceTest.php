<?php
// phpcs:ignoreFile
/**
 * WPC Product Bundles adds `is_fixed_price()` to its product class (WC_Product_Woosb) and src/
 * detects it via method_exists(). Mockery only makes method_exists() true for methods declared
 * on the mocked type, so tests mock `WC_Product` together with this interface.
 */

if ( ! interface_exists( 'MelhorEnvioTestsWoosbFixedPriceProduct' ) ) {
	interface MelhorEnvioTestsWoosbFixedPriceProduct {

		/** @return bool */
		public function is_fixed_price();
	}
}
