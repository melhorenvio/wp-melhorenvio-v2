<?php

namespace MelhorEnvio\Controllers;

class PathController {

	/**
	 * Function to return the patch from the WordPress plugins folder,
	 * returning the default and customized path by the administrator.
	 *
	 * @return json
	 */
	public function getPathPlugin() {
		return wp_send_json(
			array(
				'custom' => get_option( 'melhor_envio_path_plugins', false ),
				'native' => WP_PLUGIN_DIR,
			)
		);
	}
}
