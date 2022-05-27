<?php

namespace MelhorEnvio\Controllers;

use MelhorEnvio\Helpers\SanitizeHelper;
use MelhorEnvio\Helpers\WpNonceValidatorHelper;
use MelhorEnvio\Services\AgenciesService;

class AgenciesController {

	/**
	 * User selected function to return agencies
	 *
	 * @return json
	 */
	public function get() {
		try {

			WpNonceValidatorHelper::check( $_GET['_wpnonce'], 'save_configurations' );

			if ( empty( $_GET['state'] ) ) {
				return wp_send_json(
					array(
						'message' => 'É necessário informar o estado para reallizar a busca de agências',
					),
					400
				);
			}

			return wp_send_json(
				( new AgenciesService( SanitizeHelper::apply( $_GET ) ) )->get(),
				200
			);
		} catch ( \Exception $exception ) {
			return wp_send_json(
				array(
					'success' => false,
					'message' => 'Ocorreu um erro ao obter as agências',
				),
				500
			);
		}
	}
}
