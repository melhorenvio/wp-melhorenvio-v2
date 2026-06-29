<?php

namespace MelhorEnvio\Services;

/**
 * Class UserWooCommerceDataService
 *
 * @package Services
 */
class UserWooCommerceDataService {

	/**
	 * Function to get the user's address.
	 *
	 * @return array
	 */
	public function get() {
		global $woocommerce;

		return array(
			'postcode' => $woocommerce->customer->get_shipping_postcode(),
			'city'     => $woocommerce->customer->get_shipping_city(),
			'uf'       => $woocommerce->customer->get_shipping_state(),
			'address'  => $woocommerce->customer->get_shipping_address(),

		);
	}

	/**
	 *
	 * Function to define the user's address obtained in the "Melhor Envio" to woocommerce
	 *
	 * @param array $destination
	 * @param bool  $needReturn
	 * @return array
	 */
	public function set( $destination, $needReturn ) {
		global $woocommerce;

		if ( ! empty( $destination->cep ) ) {
			$woocommerce->customer->set_shipping_postcode( $destination->cep );
		}

		if ( ! empty( $destination->cidade ) ) {
			$woocommerce->customer->set_shipping_city( $destination->cidade );
		}

		if ( ! empty( $destination->uf ) ) {
			$woocommerce->customer->set_shipping_state( $destination->uf );
		}

		if ( ! empty( $destination->logradouro ) ) {
			$woocommerce->customer->set_shipping_address( $destination->logradouro );
		}

		if ( $needReturn ) {
			return $this->get();
		}
	}
}
