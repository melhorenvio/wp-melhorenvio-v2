<?php

namespace MelhorEnvio\Models;

use MelhorEnvio\Models\Agency;
use MelhorEnvio\Models\Session;
use MelhorEnvio\Controllers\TokenController;
use MelhorEnvio\Services\RequestService;

class Address {

	const URL = 'https://api.melhorenvio.com';

	const OPTION_ADDRESS = 'melhorenvio_address';

	const OPTION_ADDRESSES = 'melhorenvio_addresses';

	const OPTION_ADDRESS_SELECTED = 'melhorenvio_address_selected_v2';

	const SESSION_ADDRESS_SELECTED = 'melhorenvio_address_selected_v2';

	const ROUTE_MELHOR_ENVIO_ADDRESS = '/addresses';

	/**
	 *
	 * @return void
	 */
	public function getAddressesShopping() {
		$response = ( new RequestService() )->request(
			self::ROUTE_MELHOR_ENVIO_ADDRESS,
			'GET',
			array(),
			false
		);

		if ( empty( $response->data ) ) {
			return array(
				'success' => false,
				'message' => 'Não foi possível obter endereços da API do Melhor Envio',
			);
		}

		$selectedAddress = get_option( self::OPTION_ADDRESS_SELECTED );

		$addresses = array();

		foreach ( $response->data as $address ) {
			$addresses[] = array(
				'id'          => $address->id,
				'address'     => $address->address,
				'complement'  => $address->complement,
				'label'       => $address->label,
				'postal_code' => str_pad( $address->postal_code, 8, 0, STR_PAD_LEFT ),
				'number'      => $address->number,
				'district'    => $address->district,
				'city'        => $address->city->city,
				'state'       => $address->city->state->state_abbr,
				'country'     => $address->city->state->country->id,
				'selected'    => ( $selectedAddress == $address->id ),
			);
		}

		return array(
			'success'   => true,
			'origin'    => 'api',
			'addresses' => $addresses,
		);
	}

	public function setAddressShopping( $addressId ) {
		$codeStore = hash( 'sha512', get_option( 'home' ) );

		$_SESSION[ Session::ME_KEY ][ $codeStore ][ self::SESSION_ADDRESS_SELECTED ] = $addressId;

		$addressDefault = get_option( self::OPTION_ADDRESS_SELECTED );

		if ( empty( $addressDefault ) ) {
			add_option( self::OPTION_ADDRESS_SELECTED, $addressId );
			return array(
				'success' => true,
				'id'      => $addressId,
			);
		}

		update_option( self::OPTION_ADDRESS_SELECTED, $addressId );
		return array(
			'success' => true,
			'id'      => $addressId,
		);
	}

	/**
	 * Return ID of address selected by user
	 *
	 * @return int
	 */
	public function getSelectedAddressId() {
		// Find ID on session
		if ( $this->existsAddressIdSelectedSession() ) {
			$codeStore = hash( 'sha512', get_option( 'home' ) );
			return $_SESSION[ Session::ME_KEY ][ $codeStore ][ self::SESSION_ADDRESS_SELECTED ];
		}

		// Find ID on database WordPress
		$idSelected = get_option( self::OPTION_ADDRESS_SELECTED, true );
		if ( ! is_bool( $idSelected ) ) {
			return $idSelected;
		}

		return null;
	}

	public function getAddressFrom() {
		$addresses = $this->getAddressesShopping();

		$idAddressSelected = $this->getSelectedAddressId();

		if ( is_null( $addresses['addresses'] ) ) {
			return null;
		}

		foreach ( $addresses['addresses'] as $item ) {
			if ( $item['id'] == floatval( $idAddressSelected ) ) {
				return array(
					'success' => true,
					'origin'  => 'session/database',
					'address' => $item,
				);
			}
		}

		if ( ! empty( $addresses['addresses'] ) ) {
			return array(
				'success' => true,
				'origin'  => 'database',
				'address' => end( $addresses['addresses'] ),
			);
		}

		return array(
			'success' => false,
			'address' => array(),
		);
	}

	/**
	 * function check has in session the ID of address selected
	 *
	 * @return bool
	 */
	private function existsAddressIdSelectedSession() {
		$codeStore = hash( 'sha512', get_option( 'home' ) );

		return ! empty( $_SESSION[ Session::ME_KEY ][ $codeStore ][ self::SESSION_ADDRESS_SELECTED ] );
	}
}
