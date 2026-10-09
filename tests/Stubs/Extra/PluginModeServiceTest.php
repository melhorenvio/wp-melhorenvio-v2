<?php
// phpcs:ignoreFile
/**
 * Extra fakes for PluginModeServiceTest.
 *
 * PluginModeService calls WC_Cache_Helper::get_transient_version() statically after toggling
 * the shipping method; this fake records each call in WC_Cache_Helper::$calls.
 */

if ( ! class_exists( 'WC_Cache_Helper' ) ) {
	class WC_Cache_Helper {

		/** @var array<int, array{0: string, 1: bool}> */
		public static $calls = array();

		public static function get_transient_version( $group, $refresh = false ) {
			self::$calls[] = array( $group, $refresh );
			return '1';
		}
	}
}
