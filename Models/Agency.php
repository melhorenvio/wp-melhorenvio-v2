<?php

namespace MelhorEnvio\Models;

class Agency {

	const AGENCY_SELECTED = 'melhorenvio_option_agency';

	const AGENCY_SELECTED_JADLOG_CENTRALIZED = 'melhorenvio_option_agency_jadlog_centralized';

	const AGENCY_SELECTED_CORREIOS_CENTRALIZED = 'melhorenvio_option_agency_correios_centralized';

	/**
	 * function to get the id of agency Jadlog selected.
	 *
	 * @return array
	 */
	public function get() {
		return get_option( self::AGENCY_SELECTED, array() );
	}

	/**
	 * function to get the id of agency Jadlog centralized selected.
	 *
	 * @return array
	 */
	public function getJadlogCentralized() {
		return get_option( self::AGENCY_SELECTED_JADLOG_CENTRALIZED, null );
	}

	/**
	 * function to get the id of agency Correios centralized selected.
	 *
	 * @return array
	 */
	public function getCorreiosCentralized() {
		return get_option( self::AGENCY_SELECTED_CORREIOS_CENTRALIZED, null );
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

	/**
	 * @param array $data
	 * @return bool
	 */
	public function setJadlogCentralized( $data ) {
		delete_option( self::AGENCY_SELECTED_JADLOG_CENTRALIZED );
		if ( ! add_option( self::AGENCY_SELECTED_JADLOG_CENTRALIZED, $data ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @param array $data
	 * @return bool
	 */
	public function setCorreiosCentralized( $data ) {
		delete_option( self::AGENCY_SELECTED_CORREIOS_CENTRALIZED );
		if ( ! add_option( self::AGENCY_SELECTED_CORREIOS_CENTRALIZED, $data ) ) {
			return false;
		}

		return true;
	}

}
