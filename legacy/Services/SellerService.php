<?php

namespace MelhorEnvio\Services;

use MelhorEnvio\Models\Address;
use MelhorEnvio\Models\Seller;

/**
 * Class responsible for the service of managing the store salesperson
 */
class SellerService {

	/**
	 * Get data user on API Melhor Envio
	 *
	 * @return object $dataSeller
	 */
	public function getData() {
		$configurationService = new ConfigurationsService();

		$label = $configurationService->getLabel();

		if ( ! empty( $label ) ) {
			$label = (array) $label;
			if ( ! empty( $label['state'] ) ) {
				$label['state_abbr'] = $label['state'];
			}
			return (object) $label;
		}

		$origin = $configurationService->getAddresses();

		$seller = new Seller();

		$data = $seller->get();

		if ( ! empty( $data ) ) {
			$configurationService->setLabel( $data );
			return $data;
		}

		$data = $this->getDataApiMelhorEnvio();

		$address = ( new Address() )->getAddressFrom();

		$store = ( new StoreService() )->getStoreSelected();

		if ( ! empty( $address['address']['id'] ) ) {
			$data->address->address                 = ! empty( $address['address']['address'] ) ? $address['address']['address'] : null;
			$data->address->complement              = ! empty( $address['address']['complement'] )
				? $address['address']['complement']
				: null;
			$data->address->number                  = ! empty( $address['address']['number'] )
				? $address['address']['number']
				: null;
			$data->address->district                = ! empty( $address['address']['district'] )
				? $address['address']['district']
				: null;
			$data->address->city->city              = ! empty( $address['address']['city'] ) ? $address['address']['city'] : null;
			$data->address->city->state->state_abbr = ! empty( $address['address']['state'] )
				? $address['address']['state']
				: null;
			$data->address->postal_code             = ! empty( $address['address']['postal_code'] )
				? $address['address']['postal_code']
				: null;
		}

		$data = array(
			'name'                   => ! empty( $store->name )
				? $store->name
				: sprintf( '%s %s', $data->firstname, $data->lastname ),
			'phone'                  => ! empty( $data->phone->phone ) ? $data->phone->phone : null,
			'email'                  => ! empty( $store->email ) ? $store->email : $data->email,
			'document'               => ! empty( $store->document ) ? null : $data->document,
			'company_document'       => ! empty( $store->document ) ? $store->document : null,
			'economic_activity_code' => ! empty( $store->economic_activity_code )
				? $store->economic_activity_code
				: null,
			'address'                => ! empty( $store->address->address ) ? $store->address->address : $data->address->address,
			'complement'             => ! empty( $store->address->complement )
				? $store->address->complement
				: $data->address->complement,
			'number'                 => ! empty( $store->address->number ) ? $store->address->number : $data->address->number,
			'district'               => ! empty( $store->address->district ) ? $store->address->district : $data->address->district,
			'city'                   => ! empty( $store->address->city->city ) ? $store->address->city->city : $data->address->city->city,
			'state_abbr'             => ! empty( $store->address->city->state->state_abbr )
				? $store->address->city->state->state_abbr
				: $data->address->city->state->state_abbr,
			'country_id'             => 'BR',
			'postal_code'            => ! empty( $store->address->postal_code )
				? $store->address->postal_code
				: $data->address->postal_code,
		);

		$seller->save( $data );
		$configurationService->setLabel( $data );

		return (object) $data;
	}

	/**
	 * Get data user on API Melhor Envio
	 *
	 * @return object $data
	 */
	public function getDataApiMelhorEnvio() {
		$data = ( new RequestService() )->request( '', 'GET', array(), false );

		if ( ! isset( $data->id ) ) {
			return array(
				'success' => false,
				'message' => 'Usuário não encontrado no Melhor Envio',
			);
		}

		return $data;
	}
}
