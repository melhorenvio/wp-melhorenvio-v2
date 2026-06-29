<?php

namespace MelhorEnvio\Controllers;

use MelhorEnvio\Services\NoticeFormService;

class NoticeFormController {

	/**
	 * Function to show the form alert
	 *
	 * @return json
	 */
	public function showForm() {
		$data = ( new NoticeFormService() )->showForm();
		return wp_send_json(
			array(
				'result' => $data,
			)
		);
	}

	/**
	 * Function to hide the form alert
	 *
	 * @return json
	 */
	public function hideForm() {
		$data = ( new NoticeFormService() )->hideForm();
		return wp_send_json(
			array(
				'result' => $data,
			)
		);
	}
}
