<?php

namespace MelhorEnvio\Services;

class BalanceService {

	/**
	 * function to get balance user.
	 *
	 * @return array
	 */
	public function get() {
		$response = ( new RequestService() )->request(
			'/balance',
			'GET',
			array(),
			false
		);

		if ( isset( $response->balance ) ) {
			return array(
				'success' => true,
				'balance' => 'R$' . number_format( $response->balance, 2, ',', '.' ),
				'value'   => $response->balance,
			);
		}

		return array(
			'success' => false,
			'message' => 'Erro ao conectar a API',
		);
	}
}
