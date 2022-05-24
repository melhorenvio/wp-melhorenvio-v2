<?php

namespace MelhorEnvio\Models;

class Token {

	const OPTION_TOKEN = 'wpmelhorenvio_token';

	const OPTION_TOKEN_SANDBOX = 'wpmelhorenvio_token_sandbox';

	const OPTION_TOKEN_ENVIRONMENT = 'wpmelhorenvio_token_environment';

	const PRODUCTION = 'production';

	const SANDBOX = 'sandbox';

	/**
	 * function to get tokens in options WordPress.
	 *
	 * @return array
	 */
	public function get() {
		$environment = get_option( self::OPTION_TOKEN_ENVIRONMENT, self::PRODUCTION );

		$environment = ( in_array( $environment, array( self::PRODUCTION, self::SANDBOX ) ) )
			? $environment
			: self::PRODUCTION;

		return array(
			'token'             => get_option( self::OPTION_TOKEN, '' ),
			'token_sandbox'     => get_option( self::OPTION_TOKEN_SANDBOX, '' ),
			'token_environment' => $environment,
		);
	}

	/**
	 * @param string $token
	 * @param string $tokenSandbox
	 * @param string $environment
	 * @return array $data
	 */
	public function save( $token, $tokenSandbox, $environment ) {
		delete_option( self::OPTION_TOKEN );
		delete_option( self::OPTION_TOKEN_SANDBOX );
		delete_option( self::OPTION_TOKEN_ENVIRONMENT );

		return array(
			'token'             => add_option( self::OPTION_TOKEN, $token, true ),
			'token_sandbox'     => add_option( self::OPTION_TOKEN_SANDBOX, $tokenSandbox, true ),
			'token_environment' => add_option( self::OPTION_TOKEN_ENVIRONMENT, $environment, true ),
		);
	}
}
