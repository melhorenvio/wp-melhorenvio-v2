<?php

namespace MelhorEnvio\Models;

class AgencyLatam {

	/**
	 * Constant to store in WordPress options the agency ID "Latam"
	 * selected by the user.
	 */
	const AGENCY_ID_LATAM_SELECTED_USER = 'melhorenvio_agency_latam_v2';

	/**
	 * function to get the id of agency latam selected.
	 *
	 * @return bool|int
	 */
	public function getSelected() {
		$id = get_option( self::AGENCY_ID_LATAM_SELECTED_USER, false );

		return ( empty( $id ) ) ? false : intval( $id );
	}

	/**
	 * function to save the Latam agency id by the user
	 *
	 * @param string $id
	 * @return bool
	 */
	public function setAgency( $id ) {
		delete_option( self::AGENCY_ID_LATAM_SELECTED_USER );
		return add_option( self::AGENCY_ID_LATAM_SELECTED_USER, $id );
	}
}
