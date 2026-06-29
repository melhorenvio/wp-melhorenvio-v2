<?php

declare(strict_types=1);

namespace MelhorEnvio\Infrastructure\WordPress\Security;

final class SecurityHelper {

	public static function sanitizeText( mixed $input ): string {
		return strip_tags( (string) $input );
	}

	public static function sanitizeTextarea( mixed $input ): string {
		return strip_tags( (string) $input );
	}

	public static function sanitizeEmail( mixed $input ): string {
		return filter_var( (string) $input, FILTER_SANITIZE_EMAIL );
	}

	public static function sanitizeUrl( mixed $input ): string {
		return filter_var( (string) $input, FILTER_SANITIZE_URL );
	}

	public static function sanitizeInt( mixed $input ): int {
		return abs( (int) $input );
	}

	public static function sanitizeFloat( mixed $input ): float {
		return (float) filter_var( $input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
	}

	public static function escapeHtml( mixed $output ): string {
		return htmlspecialchars( (string) $output, ENT_QUOTES, 'UTF-8' );
	}

	public static function escapeAttr( mixed $output ): string {
		return htmlspecialchars( (string) $output, ENT_QUOTES, 'UTF-8' );
	}

	public static function escapeUrl( mixed $output ): string {
		return filter_var( (string) $output, FILTER_SANITIZE_URL );
	}

	public static function escapeJs( mixed $output ): string {
		return htmlspecialchars( (string) $output, ENT_COMPAT, 'UTF-8' );
	}

	public static function createNonce( string $action ): string {
		if ( function_exists( 'wp_create_nonce' ) ) {
			return wp_create_nonce( $action );
		}

		return md5( $action . time() );
	}

	public static function verifyNonce( string $nonce, string $action ): bool {
		if ( function_exists( 'wp_verify_nonce' ) ) {
			return wp_verify_nonce( $nonce, $action ) !== false;
		}

		return ! empty( $nonce );
	}

	public static function currentUserCan( string $capability ): bool {
		if ( function_exists( 'current_user_can' ) ) {
			return current_user_can( $capability );
		}

		return true;
	}
}
