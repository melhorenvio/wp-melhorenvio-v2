<?php

namespace MelhorEnvio\Helpers;

use MelhorEnvio\Models\Option;

class OptionsHelper {

	/**
	 * @return void
	 */
	public function getName( $id, $method, $company, $label ) {
		if ( is_null( $method ) && is_null( $company ) ) {
			return array(
				'method'  => $label,
				'company' => '',
			);
		}

		global $wpdb;

		$sql = sprintf( "select * from %soptions where option_name = 'melhor_envio_option_method_shipment_%s'", $wpdb->prefix, (string) $id );

		$results = $wpdb->get_results( $sql );

		if ( ! $results ) {
			return array(
				'method'  => $method,
				'company' => $company,
			);
		}

		$data = $results[0];
		$data = unserialize( $data->option_value );

		if ( ! isset( $data['name'] ) || $data['name'] == '' || $data['name'] == 'undefined' && ! is_null( $company ) ) {
			return array(
				'method'  => $method,
				'company' => $company,
			);
		}

		return array(
			'method'  => $data['name'],
			'company' => '',
		);
	}

}

