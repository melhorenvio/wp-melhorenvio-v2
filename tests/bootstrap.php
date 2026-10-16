<?php

declare(strict_types=1);

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Classes under src/ bail out with `exit` when ABSPATH is missing.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', sys_get_temp_dir() . '/wordpress/' );
}

foreach (
	array(
		'MINUTE_IN_SECONDS' => 60,
		'HOUR_IN_SECONDS'   => 3600,
		'DAY_IN_SECONDS'    => 86400,
		'WEEK_IN_SECONDS'   => 604800,
	) as $constant => $value
) {
	if ( ! defined( $constant ) ) {
		define( $constant, $value );
	}
}

// WordPress core polyfills these PHP 8 functions (wp-includes/compat.php); mirror it for PHP 7.4.
if ( ! function_exists( 'str_contains' ) ) {
	function str_contains( string $haystack, string $needle ): bool {
		return $needle === '' || strpos( $haystack, $needle ) !== false;
	}
}

if ( ! function_exists( 'str_starts_with' ) ) {
	function str_starts_with( string $haystack, string $needle ): bool {
		return strncmp( $haystack, $needle, strlen( $needle ) ) === 0;
	}
}

if ( ! function_exists( 'str_ends_with' ) ) {
	function str_ends_with( string $haystack, string $needle ): bool {
		return $needle === '' || substr( $haystack, -strlen( $needle ) ) === $needle;
	}
}

require_once __DIR__ . '/Stubs/WordPress.php';
require_once __DIR__ . '/Stubs/WooCommerce.php';
