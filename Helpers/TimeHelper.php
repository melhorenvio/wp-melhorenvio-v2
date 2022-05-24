<?php

namespace MelhorEnvio\Helpers;

use MelhorEnvio\Services\OptionsMethodShippingService;

class TimeHelper {

	/**
	 * Function to define custom delivery time
	 *
	 * @param array  $data
	 * @param object $extra
	 *
	 * @return string
	 */
	public static function label( $data, $extra ) {
		$min = intval( $data->min ) + intval( $extra );
		$max = intval( $data->max ) + intval( $extra );

		if ( empty( $data ) ) {
			return ' (*)';
		}

		if ( $max == 1 ) {
			return ' (1 dia útil)';
		}

		if ( $min == $max ) {
			return sprintf( ' (%s dias úteis)', $max );
		}

		if ( $min < $max ) {
			return sprintf( ' (%s a %s dias úteis)', $min, $max );
		}

		return sprintf( ' (%s dias úteis)', $max );
	}

	/**
	 * @param string $date
	 * @return float
	 */
	public static function getDiffFromNowInSeconds( $date ) {
		$now   = date( 'Y-m-d H:i:s' );
		$start = strtotime( $now );
		$end   = strtotime( $date );
		return $start - $end;
	}
}
