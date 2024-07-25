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
	public function getLoggi() {
		return $this->agencyModel->getLoggi();
	}

	/**
	 * @return array
	 */
	public function getJet() {
		return $this->agencyModel->getJet();
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
	public function setLoggi( $data ) {

		if ( empty( $data ) ) {
			return true;
		}

		return $this->agencyModel->setLoggi( $data );
	}

	/**
	 * @param array $data
	 * @return bool
	 */
	public function setJet( $data ) {
		if ( empty( $data ) ) {
			return true;
		}

		return $this->agencyModel->setJet( $data );
	}
}
