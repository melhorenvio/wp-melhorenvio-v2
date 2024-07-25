<?php

namespace MelhorEnvio\Models;

class Agency {

	const AGENCY_SELECTED = 'melhorenvio_option_agency';

	const AGENCY_SELECTED_JADLOG_CENTRALIZED = 'melhorenvio_option_agency_jadlog_centralized';

	const AGENCY_SELECTED_LOGGI = 'melhorenvio_option_agency_loggi';

	const AGENCY_SELECTED_JET = 'melhorenvio_option_agency_jet';

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
	 * function to get the id of agency Loggi selected.
	 *
	 * @return array
	 */
	public function getLoggi() {
		return get_option( self::AGENCY_SELECTED_LOGGI, null );
	}


	/**
	 * function to get the id of agency Jet selected.
	 *
	 * @return array
	 */
	public function getJet() {
		return get_option( self::AGENCY_SELECTED_JET, null );
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
	public function setLoggi( $data ) {
		delete_option( self::AGENCY_SELECTED_LOGGI );
		if ( ! add_option( self::AGENCY_SELECTED_LOGGI, $data ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @param array $data
	 * @return bool
	 */
	public function setJet( $data ) {
		delete_option( self::AGENCY_SELECTED_JET );
		if ( ! add_option( self::AGENCY_SELECTED_JET, $data ) ) {
			return false;
		}

		return true;
	}

}
