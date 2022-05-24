<?php

namespace MelhorEnvio\Controllers;

use MelhorEnvio\Helpers\SanitizeHelper;
use MelhorEnvio\Services\ManageRequestService;

class RequestsController {

	/**
	 * Controller function to get items in log requests
	 *
	 * @return array
	 */
	public function getLogs() {
		$ordering = 'time';
		if ( isset( $_GET['ordering'] ) && in_array( $_GET['ordering'], array( 'time', 'status_code', 'type', 'date' ) ) ) {
			$ordering = SanitizeHelper::apply( $_GET['ordering'] );
		}

		return wp_send_json(
			array(
				'data' => ( new ManageRequestService() )->get( $ordering ),
			),
			\WP_Http::OK
		);
	}

	/**
	 * Controller function to delte all  items in log requests
	 *
	 * @return array
	 */
	public function deleteLogs() {
		return wp_send_json(
			array(
				'data' => ( new ManageRequestService() )->deleteAll(),
			),
			\WP_Http::OK
		);
	}
}
