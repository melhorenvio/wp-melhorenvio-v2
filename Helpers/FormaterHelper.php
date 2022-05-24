<?php

namespace MelhorEnvio\Helpers;

class FormaterHelper {

	/**
	 * Remove characters and use only numbers.
	 *
	 * @param string $phone
	 * @return string $phone
	 */
	public static function formatPhone( $phone ) {
		return str_replace( array( '(', ')', '-', ' ' ), array( '' ), $phone );
	}

	/**
	 * function to insert mask phone
	 *
	 * @param string $phone
	 * @return string
	 */
	public function maskPhone( $phone ) {
		$phone   = preg_replace( '/\D/', '', $phone );
		$string  = '';
		$string .= '(' . substr( $phone, 0, 2 ) . ') ';
		$string .= substr( $phone, 2, 4 ) . '-' . substr( $phone, 6, 10 );
		return $string;
	}

	/**
	 * Remove characters and use only numbers.
	 *
	 * @param string $document
	 * @return string $document
	 */
	public static function formatDocument( $document ) {
		return str_replace( array( '-', '.', '/', ' ' ), array( '' ), $document );
	}
}
