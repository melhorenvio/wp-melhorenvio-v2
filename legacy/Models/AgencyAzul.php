<?php

namespace MelhorEnvio\Models;

class AgencyAzul {

	/**
	 * Constant to store in WordPress options the agency ID "Azul Cargo Express"
	 * selected by the user.
	 */
	const AGENCY_ID_AZUL_CARGO_SELECTED_USER = 'melhorenvio_agency_azul_v2';

	/**
	 * function to get the id of agency azul selected.
	 *
	 * @return bool|int
	 */
	public function getSelected() {
		$id = get_option( self::AGENCY_ID_AZUL_CARGO_SELECTED_USER, false );

		return ( empty( $id ) ) ? false : intval( $id );
	}

	/**
	 * function to save the Azul Cargo agency id by the user
	 *
	 * @param string $id
	 * @return bool
	 */
	public function setAgency( $id ) {
		delete_option( self::AGENCY_ID_AZUL_CARGO_SELECTED_USER );
		return add_option( self::AGENCY_ID_AZUL_CARGO_SELECTED_USER, $id );
	}
}
