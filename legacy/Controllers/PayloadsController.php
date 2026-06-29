<?php

namespace MelhorEnvio\Controllers;

use MelhorEnvio\Models\Payload;
use MelhorEnvio\Services\PayloadService;

class PayloadsController {

	const PAYLOAD_NOT_FOUND = 'Payload não encontrado para o post id ';

	const PAYLOAD_ERROR = 'Ocorreu um erro ao obter o payload';

	/**
	 * controller to show payload by order
	 *
	 * @param int $postId
	 * @return json
	 */
	public function show( $postId ) {
		try {
			$payload = ( new PayloadService() )->getPayloadHideImportantData( $postId );

			if ( empty( $payload ) ) {
				return wp_send_json(
					array(
						'message' => self::PAYLOAD_NOT_FOUND . $postId,
					),
					404
				);
			}

			return wp_send_json( $payload, 200 );
		} catch ( \Exception $exception ) {
			return wp_send_json(
				array(
					'message' => self::PAYLOAD_ERROR,
				),
				400
			);
		}
	}

	/**
	 * controller to show payload by order
	 *
	 * @param int $postId
	 * @return json
	 */
	public function showLogged( $postId ) {
		try {
			$payload = ( new Payload() )->get( $postId );

			if ( empty( $payload ) ) {
				return wp_send_json(
					array(
						'message' => self::PAYLOAD_NOT_FOUND . $postId,
					),
					404
				);
			}

			return wp_send_json( $payload, 200 );
		} catch ( \Exception $exception ) {
			return wp_send_json(
				array(
					'message' => self::PAYLOAD_ERROR,
				),
				400
			);
		}
	}

	/**
	 * function to destroy payload by post id.
	 *
	 * @param int $postId
	 * @return json
	 */
	public function destroy( $postId ) {
		try {
			if ( ( new Payload() )->destroy( $postId ) ) {
				return wp_send_json(
					array(
						'success' => true,
					),
					200
				);
			}

			return wp_send_json(
				array(
					'success' => false,
					'error'   => 'Não foi possível remover o payload',
				),
				400
			);
		} catch ( \Exception $exception ) {
			return wp_send_json(
				array(
					'success' => false,
					'error'   => 'Ocorreu um erro ao remover o payload',
				),
				400
			);
		}
	}

	/**
	 * function to retrieve payload to insert item cart.
	 *
	 * @param int $postId
	 * @return json
	 */
	public function showPayloadCart( $postId, $service ) {
		try {
			$payload = ( new PayloadService() )->getPayloadToCart( $postId, $service );

			if ( empty( $payload ) ) {
				return wp_send_json(
					array(
						'message' => self::PAYLOAD_NOT_FOUND . $postId,
					),
					404
				);
			}

			return wp_send_json( $payload, 200 );
		} catch ( \Exception $exception ) {
			return wp_send_json(
				array(
					'message' => self::PAYLOAD_ERROR,
				),
				400
			);
		}
	}
}
