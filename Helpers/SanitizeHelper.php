<?php

namespace MelhorEnvio\Helpers;

class SanitizeHelper {

	public static function apply( $data ) {
		return map_deep(
			wp_unslash( $data ),
			'sanitize_text_field'
		);
	}
}
