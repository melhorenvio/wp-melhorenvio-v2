<?php
// phpcs:ignoreFile
/**
 * Extra fakes for CartItemsBuilderServiceTest.
 *
 * CartItemsBuilderService probes WPC Product Bundles / WPC Composite Products products with
 * method_exists(). Mockery only makes method_exists() true for methods declared on the mocked
 * types, so tests mock `WC_Product` together with these interfaces, e.g.
 * Mockery::mock( 'WC_Product, CartItemsBuilderServiceTestWoosbProduct' ).
 *
 * WPCleverWooco_Helper fake: seed WPCleverWooco_Helper::$items; requested ids are recorded in
 * WPCleverWooco_Helper::$requested.
 */

if ( ! interface_exists( 'CartItemsBuilderServiceTestWoosbProduct' ) ) {
	interface CartItemsBuilderServiceTestWoosbProduct {

		public function get_items();

		public function build_items( $ids = null );

		public function get_sale_price( $context = 'view' );

		public function get_discount_percentage();

		public function is_fixed_price();

		public function has_optional();
	}
}

if ( ! interface_exists( 'CartItemsBuilderServiceTestWoocoProduct' ) ) {
	interface CartItemsBuilderServiceTestWoocoProduct {

		public function get_discount();
	}
}

if ( ! class_exists( 'WPCleverWooco_Helper' ) ) {
	class WPCleverWooco_Helper {

		/** @var array<int, array<string, mixed>> */
		public static $items = array();

		/** @var string[] */
		public static $requested = array();

		public static function get_items( $ids, $product_id = 0, $context = '' ) {
			self::$requested[] = $ids;
			return self::$items;
		}
	}
}
