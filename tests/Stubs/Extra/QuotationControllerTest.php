<?php
// phpcs:ignoreFile
/**
 * Extra fakes for QuotationControllerTest.
 *
 * CartItemsBuilderService::bundleHasOptionalItems() probes the product with method_exists(),
 * which a plain Mockery mock of an undeclared class can't satisfy. Mock with
 * `Mockery::mock( 'WC_Product, QuotationControllerTestWoosbProduct' )` to expose the method.
 */

if ( ! interface_exists( 'QuotationControllerTestWoosbProduct' ) ) {
	interface QuotationControllerTestWoosbProduct {

		public function has_optional();
	}
}
