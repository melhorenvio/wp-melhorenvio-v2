<?php

namespace MelhorEnvio\Services;

class ListPluginsIncompatiblesService {

	/**
	 * Function to init a seach by plugins incompatibles.
	 *
	 * @return void
	 */
	public function init() {
		$installed = $this->getListPluginsInstalled();

		$incompatibles = $this->getListPluginsIncompatibles();

		if ( empty( $installed ) || empty( $incompatibles ) ) {
			return false;
		}

		foreach ( $installed as $plugin ) {
			if ( in_array( $plugin, $incompatibles ) ) {
				( new SessionNoticeService() )->add(
					sprintf( 'O plugin <b>%s</b> pode ser incompatível com o plugin do Melhor Envio.', $plugin ),
					SessionNoticeService::NOTICE_INFO
				);
			}
		}
	}

	/**
	 * Function to return a list with plugins installed in WP.
	 *
	 * @return array
	 */
	public function getListPluginsInstalled() {
		return apply_filters(
			'network_admin_active_plugins',
			get_option( 'active_plugins' )
		);
	}

	/**
	 * Function to retrive a list with plugins incompatibles.
	 *
	 * @return array
	 */
	public function getListPluginsIncompatibles() {
		return array(
			'wpc-composite-products/wpc-composite-products.php',
		);
	}
}
