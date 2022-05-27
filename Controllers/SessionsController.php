<?php

namespace MelhorEnvio\Controllers;

use MelhorEnvio\Services\ClearDataStored;
use MelhorEnvio\Helpers\SessionHelper;
use MelhorEnvio\Helpers\WpNonceValidatorHelper;
use MelhorEnvio\Models\Session;

class SessionsController {

	/**
	 * Function to get information from the plugin session
	 *
	 * @return json
	 */
	public function getSession() {
		SessionHelper::initIfNotExists();

		return wp_send_json( $_SESSION[ Session::ME_KEY ], 200 );
	}

	/**
	 * Function to delete information from the plugin session
	 *
	 * @return json
	 */
	public function deleteSession() {
		WpNonceValidatorHelper::check( $_GET['_wpnonce'], 'save_configurations' );
		( new ClearDataStored() )->clear();
	}
}
