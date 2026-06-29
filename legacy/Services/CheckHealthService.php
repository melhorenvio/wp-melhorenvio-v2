<?php

namespace MelhorEnvio\Services;

/**
 * Health service class
 */
class CheckHealthService {

	public function init() {}

	/**
	 * Function to check if the plugin has all the necessary plugins to run.
	 *
	 * @param string $pathPlugins
	 * @return array
	 */
	public function checkPathPlugin( $pathPlugins ) {
		$errorsPath = array();

        $sessionNoticeService = new SessionNoticeService();

		if ( ! is_dir( $pathPlugins . '/woocommerce' ) ) {
			$errorsPath[] = 'Defina o path do diretório de plugins nas configurações do plugin do Melhor Envio';
		}

		$errors = array();

		if ( ! class_exists( 'WooCommerce' ) ) {
			$errors[] = 'Você precisa do plugin WooCommerce ativado no WordPress para utilizar o plugin do Melhor Envio';
		}

		if ( ! empty( $errors ) ) {
			foreach ( $errors as $err ) {
				$sessionNoticeService->add( $err, SessionNoticeService::NOTICE_INFO );
			}
		}

		return array(
			'errors'     => $errors,
			'errorsPath' => $errorsPath,
		);
	}
}
