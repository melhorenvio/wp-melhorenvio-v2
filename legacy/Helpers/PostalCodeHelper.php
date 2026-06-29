<?php

namespace MelhorEnvio\Helpers;

class PostalCodeHelper {

	const SIZE_POSTAL_CODE = 8;

	/**
	 * Function to format postal code
	 *
	 * @param string $postalCode
	 * @return string
	 */
	public static function postalcode( $postalCode ) {
		$postalCode = ExtractNumberHelper::extractOnlyNumber( $postalCode );

		return str_pad( $postalCode, self::SIZE_POSTAL_CODE, '0', STR_PAD_LEFT );
	}
}
