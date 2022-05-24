<?php

namespace MelhorEnvio\Models;

class Agency {

	const AGENCY_SELECTED = 'melhorenvio_option_agency';

	/**
	 * function to get the id of agency Jadlog selected.
	 *
	 * @return array
	 */
	public function get() {
		return get_option( self::AGENCY_SELECTED, array() );
	}

	/**
	 * @param array $data
	 * @return bool
	 */
	public function set( $data ) {
		delete_option( self::AGENCY_SELECTED );
		if ( ! add_option( self::AGENCY_SELECTED, $data ) ) {
			return false;
		}

		return true;
	}
}
