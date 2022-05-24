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
	 * @param array $data
	 * @return bool
	 */
	public function set( $data ) {
		if ( empty( $data ) ) {
			return true;
		}

		return $this->agencyModel->set( $data );
	}
}
