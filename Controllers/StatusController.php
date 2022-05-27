<?php

namespace MelhorEnvio\Controllers;

class StatusController {

	/**
	 * Function to list woocommerce order status
	 *
	 * @return json
	 */
	public function getStatus() {

		if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'save_configurations' ) ) {
			return wp_send_json( array(), 403 );
		}

		$status = wc_get_order_statuses();
		return wp_send_json( array( 'statusWc' => $status ), 200 );
	}
}
