<?php

namespace MelhorEnvio\Services;

use MelhorEnvio\Models\Token;

class TokenService {

	/**
	 * Get token Melhor Envio.
	 *
	 * @return string $token
	 */
	public function get() {
		$tokenData = ( new Token() )->get();

		if ( ! $this->isValid( $tokenData ) ) {
			return false;
		}

		return $tokenData;
	}

	/**
	 * Service to save token Melhor Envio.
	 *
	 * @param string $token
	 * @param string $tokenSandbox
	 * @param string $tokenEnvironment
	 * @return bool
	 */
	public function save( $token, $tokenSandbox, $tokenEnvironment ) {
		$result = ( new Token() )->save( $token, $tokenSandbox, $tokenEnvironment );

		( new ClearDataStored() )->clear();
		( new SessionNoticeService() )->removeNoticeTokenInvalid();

		return ( ! empty( $result['token'] ) && ! empty( $result['token_environment'] ) );
	}

	/**
	 * function used in test to verify if has tokens.
	 *
	 * @return array
	 */
	public function check() {
		$dataToken = $this->get();

		return array(
			'environment' => $dataToken['token_environment'],
			'production'  => substr( $dataToken['token'], 0, 30 ) . '...',
			'sandbox'     => substr( $dataToken['token_sandbox'], 0, 30 ) . '...',
		);
	}

	/**
	 * function to check if user has token valid
	 *
	 * @param array $dataToken
	 * @return boolean
	 */
	public function isValid( $dataToken ) {
		if ( empty( $dataToken ) ) {
			return false;
		}

		$token = ( $dataToken['token_environment'] == Token::SANDBOX )
			? $dataToken['token_sandbox']
			: $dataToken['token'];

		if ( empty( $token ) ) {
			return false;
		}

		return true;
	}
}
