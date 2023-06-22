<?php

namespace MelhorEnvio\Services;

use MelhorEnvio\Models\Agency;

class AgenciesSelectedService {

	protected $agencyModel;

	public function __construct() {
		$this->agencyModel = new Agency();
	}

	/**
	 * @return array
	 */
	public function get() {
		return $this->agencyModel->get();
	}

	/**
	 * @return array
	 */
	public function getJadlogCentralized() {
		return $this->agencyModel->getJadlogCentralized();
	}

	/**
	 * @return array
	 */
	public function getCorreiosCentralized() {
		return $this->agencyModel->getCorreiosCentralized();
	}

	/**
	 * @param array $data
	 * @return bool
	 */
	public function set( $data ) {
		if ( empty( $data ) ) {
			return true;
		}

		return $this->agencyModel->set( $data );
	}

	/**
	 * @param array $data
	 * @return bool
	 */
	public function setJadlogCentralized( $data ) {
		if ( empty( $data ) ) {
			return true;
		}

		return $this->agencyModel->setJadlogCentralized( $data );
	}

	/**
	 * @param array $data
	 * @return bool
	 */
	public function setCorreiosCentralized( $data ) {
		if ( empty( $data ) ) {
			return true;
		}

		return $this->agencyModel->setCorreiosCentralized( $data );
	}
}
