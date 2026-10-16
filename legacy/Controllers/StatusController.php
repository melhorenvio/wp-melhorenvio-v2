<?php

namespace MelhorEnvio\Controllers;

use MelhorEnvio\Helpers\WpNonceValidatorHelper;

class StatusController {

	/**
	 * Function to list woocommerce order status
	 *
	 * @return json
	 */
	public function getStatus() {

		WpNonceValidatorHelper::check( $_GET['_wpnonce'], 'orders' );

		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
		}

		$status = wc_get_order_statuses();
		return wp_send_json( array( 'statusWc' => $status ), 200 );
	}
}
