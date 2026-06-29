<?php

namespace MelhorEnvio\Helpers;

class ExtractNumberHelper {

	/**
	 * Function to extract numbers
	 *
	 * @param string $value
	 * @return float
	 */
	public static function extractOnlyNumber( $value ) {
		return floatval( preg_replace( '/\D/', '', $value ) );
	}

}
