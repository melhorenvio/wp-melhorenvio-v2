<?php

namespace MelhorEnvio\Models;

class Seller {

	/**
	 * Identification key for the seller option in WordPress.
	 */
	const OPTION_META_SELLER = 'wp_melhor_envio_seller';

	/**
	 * Function to select the seller data in WordPress options.
	 *
	 * @return object
	 */
	public function get() {
		$seller = get_option( self::OPTION_META_SELLER, false );

		if ( ! empty( $seller ) ) {
			return json_decode( $seller );
		}

		return false;
	}

	/**
	 * Function to save the seller data in WordPress options.
	 *
	 * @param object $seller
	 * @return bool
	 */
	public function save( $seller ) {
		if ( ! empty( $this->get ) ) {
			return update_option( self::OPTION_META_SELLER, $seller, true );
		}

		return add_option( self::OPTION_META_SELLER, json_encode( $seller ), true );
	}

	/**
	 * Function to destroy the seller data in WordPress options.
	 *
	 * @return bool
	 */
	public function destroy() {
		return delete_option( self::OPTION_META_SELLER );
	}
}
