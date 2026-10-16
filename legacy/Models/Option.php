<?php

namespace MelhorEnvio\Models;

class Option {

	const OPTION_RECEIPT = 'melhorenvio_ar';

	const OPTION_OWN_HAND = 'melhorenvio_mp';

	const OPTION_INSURANCE_VALUE = 'melhorenvio_vs';

	/**
	 * @return void
	 */
	public function get() {
		$options = get_option( 'melhorenvio_options' );

		if ( ! $options ) {
			return array(
				'tax'  => 0,
				'time' => 0,
			);
		}

		return $options;
	}

	/**
	 * Function for receiving quote options (AR and MP)
	 *
	 * @return object
	 */
	public function getOptions() {
		$receipt        = get_option( self::OPTION_RECEIPT );
		$ownHand        = get_option( self::OPTION_OWN_HAND );
		$insuranceValue = get_option( self::OPTION_INSURANCE_VALUE );

		return (object) array(
			'receipt'         => filter_var( $receipt, FILTER_VALIDATE_BOOLEAN ),
			'own_hand'        => filter_var( $ownHand, FILTER_VALIDATE_BOOLEAN ),
			'insurance_value' => filter_var( $insuranceValue, FILTER_VALIDATE_BOOLEAN ),
		);
	}

	/**
	 * @param array $options
	 * @return void
	 */
	public function save( $options ) {
		$data = array(
			'tax'  => floatval( $options['tax'] ),
			'time' => intval( $options['time'] ),
		);

		delete_option( 'melhorenvio_options' );
		add_option( 'melhorenvio_options', $data );

		return array(
			'success' => true,
			'tax'     => $data['tax'],
			'time'    => $data['time'],
		);
	}
}
