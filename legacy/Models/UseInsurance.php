<?php

namespace MelhorEnvio\Models;

class UseInsurance {

	/**
	 * @return bool
	 */
	public function get() {
		$show = get_option( 'melhorenvio_use_insurancce' );

		if ( ! $show ) {
			return true;
		}

		if ( $show == '1' ) {
			return false;
		}

		return false;
	}

	/**
	 * @param string $value
	 * @return bool
	 */
	public function set( $value ) {
		if ( $value == 'true' ) {
			delete_option( 'melhorenvio_use_insurancce' );
			return true;
		} else {
			add_option( 'melhorenvio_use_insurancce', 1 );
			return false;
		}
	}
}
