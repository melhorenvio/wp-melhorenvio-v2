<?php

namespace MelhorEnvio\Controllers;

class StatusController {

	/**
	 * Function to list woocommerce order status
	 *
	 * @return json
	 */
	public function getStatus() {
		$status = wc_get_order_statuses();
		return wp_send_json( array( 'statusWc' => $status ), 200 );
	}
}
