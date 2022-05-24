<?php

namespace MelhorEnvio\Services;

/**
 * Service responsible for managing request logs
 */
class ManageRequestService {

	/**
	 * Constant for wp_options where request logs will be stored
	 */
	const WP_OPTIONS_REQUEST_LOGS = 'melhorenvio_requests_logs';

	/**
	 * Function to store the log of a request
	 *
	 * @param string $route
	 * @param int    $statusCode
	 * @param string $type
	 * @param array  $params
	 * @param array  $response
	 * @param float  $time
	 * @return void
	 */
	public function register( $route, $statusCode, $type, $params, $response, $time ) {
		$requestLogs = get_option( self::WP_OPTIONS_REQUEST_LOGS, array() );

		$requestLogs[] = array(
			'route'       => $route,
			'type'        => $type,
			'status_code' => $statusCode,
			'time'        => $time,
			'params'      => $params,
			'response'    => $response,
			'date'        => date( 'Y-m-d H:i:s' ),
		);

		update_option( self::WP_OPTIONS_REQUEST_LOGS, $requestLogs );
	}

	/**
	 * Function to fetch all request logs
	 *
	 * @param string $ordering
	 * @return array
	 */
	public function get( $ordering ) {
		if ( empty( $ordering ) ) {
			$ordering = 'time';
		}

		return $this->filterRegisters( get_option( self::WP_OPTIONS_REQUEST_LOGS, array() ), $ordering );
	}

	 /**
	  * Function to delete all request logs
	  *
	  * @return bool
	  */
	public function deleteAll() {
		return delete_option( self::WP_OPTIONS_REQUEST_LOGS );
	}

	/**
	 * Function to filter the request logs
	 *
	 * @param array  $requests
	 * @param string $ordering
	 * @return array
	 */
	public function filterRegisters( $requests, $ordering ) {
		if ( empty( $requests ) ) {
			return $requests;
		}

		$dateLimit = date( 'Y-m-d', strtotime( '-1 months' ) );

		foreach ( $requests as $key => $request ) {
			$dateLog = date( 'Y-m-d', strtotime( $request['date'] ) );
			if ( $dateLog < $dateLimit ) {
				unset( $requests[ $key ] );
			}
		}

		update_option( self::WP_OPTIONS_REQUEST_LOGS, $requests );

		usort(
			$requests,
			function( $a, $b ) use ( $ordering ) {
				return $a[ $ordering ] > $b[ $ordering ];
			}
		);

		return $requests;

	}

}
