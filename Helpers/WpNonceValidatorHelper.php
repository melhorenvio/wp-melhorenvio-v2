<?php

namespace MelhorEnvio\Helpers;

class WpNonceValidatorHelper {

	public static function check( $wp_nonce, $type ) {
		if ( ! wp_verify_nonce( $wp_nonce, $type ) ) {
			return wp_send_json( array(), 403 );
		}
	}
}
