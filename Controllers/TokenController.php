<?php

namespace MelhorEnvio\Controllers;

use MelhorEnvio\Helpers\SanitizeHelper;
use MelhorEnvio\Services\TokenService;

class TokenController {

    const WP_NONCE = '_wpnonce';
	/**
	 * Function to return data of user token.
	 *
	 * @return json
	 */
	public function get() {

		if ( ! wp_verify_nonce( $_GET[self::WP_NONCE], 'tokens' ) ) {
			return wp_send_json( array(), 403 );
		}

		$tokenData = ( new TokenService() )->get();
		return wp_send_json( $tokenData, 200 );
	}

	/**
	 * Function to sake data of token
	 *
	 * @param string $token
	 * @param string $token_sandbox
	 * @param string $token_environment
	 *
	 * @return json
	 */
	public function save() {

		if ( ! wp_verify_nonce( $_POST[self::WP_NONCE], 'tokens' ) ) {
			return wp_send_json( array(), 403 );
		}

		if ( ! isset( $_POST['token'] ) ) {
			return wp_send_json(
				array(
					'success' => false,
					'message' => 'Informar o Token',
				),
				400
			);
		}

		if ( ! isset( $_POST['environment'] ) ) {
			return wp_send_json(
				array(
					'success' => false,
					'message' => 'Informar o ambiente',
				),
				400
			);
		}

		$result = ( new TokenService() )->save(
			SanitizeHelper::apply( $_POST['token'] ),
			SanitizeHelper::apply( $_POST['token_sandbox'] ),
			SanitizeHelper::apply( $_POST['environment'] )
		);

		if ( $result ) {
			return wp_send_json(
				array(
					'success' => true,
					'message' => 'Token salvo com sucesso',
				),
				200
			);
		}

		return wp_send_json(
			array(
				'success' => false,
				'message' => 'Ocorreu um erro ao salvar o token',
			),
			400
		);
	}

	/**
	 * Function to check exists token instancead
	 *
	 * @return json
	 */
	public function verifyToken() {

		if ( ! wp_verify_nonce( $_GET[self::WP_NONCE], 'tokens' ) ) {
			return wp_send_json( array(), 403 );
		}

		if ( ! get_option( 'wpmelhorenvio_token' ) ) {
			return wp_send_json(
				array(
					'exists_token' => false,
				),
				200
			);
		}
		return wp_send_json(
			array(
				'exists_token' => true,
			),
			200
		);
	}
}
