<?php

namespace MelhorEnvio\Models;

use MelhorEnvio\Services\RequestService;
use MelhorEnvio\Models\Session;

class User {

	const OPTION_USER_INFO = 'melhorenvio_user_info';

	const SESSION_USER_INFO = 'melhorenvio_user_info';

	/**
	 * Return an array contain info about user
	 *
	 * @return Array
	 */
	public function get() {
		// Get info on session
		$codeStore = hash( 'sha512', get_option( 'home' ) );

		$response = ( new RequestService() )->request( '', 'GET', array(), false );

		if ( is_null( $response ) ) {
			return array(
				'success' => false,
				'message' => 'Erro ao consultar o servidor',
			);
		}

		$data = get_object_vars( $response );

		$_SESSION[ Session::ME_KEY ][ $codeStore ][ self::SESSION_USER_INFO ] = $data;

		add_option( self::OPTION_USER_INFO, $data );

		return array(
			'success' => true,
			'origin'  => 'api',
			'data'    => $data,
		);
	}

	/**
	 * Reset data about user on Database and Session
	 *
	 * @return void
	 */
	public function resetData() {
		$codeStore = hash( 'sha512', get_option( 'home' ) );

		delete_option( self::OPTION_USER_INFO, true );

		unset( $_SESSION[ Session::ME_KEY ][ $codeStore ][ self::SESSION_USER_INFO ] );
	}
}
