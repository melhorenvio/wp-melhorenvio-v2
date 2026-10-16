<?php

namespace MelhorEnvio\Controllers;

use MelhorEnvio\Services\LocationService;

class LocationsController {

	/**
	 * Function to search for address in zip code api
	 *
	 * @param string $postalCode
	 * @return json
	 */
	public function getAddressByPostalCode( $postalCode ) {
		$address = ( new LocationService() )->getAddressByPostalCode( $postalCode );

		if ( is_null( $address ) ) {
			return wp_send_json(
				array(
					'message' => sprintf( 'Não encontramos endereço para o CEP %s', $postalCode ),
				),
				404
			);
		}

		return wp_send_json(
			array(
				$address,
			),
			200
		);
	}
}
