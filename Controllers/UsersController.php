<?php

namespace MelhorEnvio\Controllers;

use MelhorEnvio\Helpers\WpNonceValidatorHelper;
use MelhorEnvio\Models\Address;
use MelhorEnvio\Models\Store;
use MelhorEnvio\Models\User;
use MelhorEnvio\Services\BalanceService;
use MelhorEnvio\Services\OrderQuotationService;
use MelhorEnvio\Services\StoreService;

class UsersController {


	const URL = 'https://api.melhorenvio.com';

	/**
	 * Function to get info by user.
	 *
	 * @return object
	 */
	public function getInfo() {
		$user = ( new User() )->get();

		return (object) array(
			'success' => true,
			'data'    => $user['data'],
		);
	}

	/**
	 * Function to fetch data about the origin of the label
	 *
	 * @return null|object
	 */
	public function getFrom() {
		$info = $this->getInfo();

		if ( isset( $info->data->message ) && preg_match( '/unauthenticated/i', $info->data->message ) ? false : true ) {

			$company = ( new StoreService() )->getStoreSelected();

			$address = ( new Address() )->getAddressFrom();

			$address = $address['address'];

			if ( is_null( $address['address'] ) ) {
				return false;
			}

			return (object) array(
				'name'             => ( isset( $company->name ) )
					? $company->name
					: $info->data['firstname'] . ' ' . $info->data['lastname'],
				'phone'            => ( isset( $info->data['phone']->phone ) ) ? $info->data['phone']->phone : null,
				'email'            => $this->handlerEmail( $info, $company ),
				'document'         => $info->data['document'],
				'company_document' => ( isset( $company->document ) ) ? $company->document : null,
				'state_register'   => ( isset( $company->state_register ) ) ? $company->state_register : null,
				'address'          => $address['address'],
				'complement'       => $address['complement'],
				'number'           => $address['number'],
				'district'         => $address['district'],
				'city'             => $address['city'],
				'state_abbr'       => $address['state'],
				'country_id'       => 'BR',
				'postal_code'      => $address['postal_code'],
			);
		}

		return false;
	}

	public function handlerEmail( $info, $company ) {
		$email = ( isset( $info->data['email'] ) ) ? $info->data['email'] : null;

		if ( empty( $email ) ) {
			$email = ( isset( $company->email ) ) ? $company->email : null;
		}

		return $email;
	}

	/**
	 * Function to fetch data about destination of the order label
	 *
	 * @param int $orderId
	 * @return object
	 */
	public function getTo( $orderId ) {
		$order = new \WC_Order( $orderId );

		$cpf  = get_post_meta( $orderId, '_billing_cpf', true );
		$cnpj = get_post_meta( $orderId, '_billing_cnpj', true );

		$phone = get_post_meta( $orderId, '_billing_cellphone', true );

		if ( empty( $phone ) ) {
			$phone = $order->get_billing_phone();
		}

		return (object) array(
			'name'             => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
			'phone'            => $phone,
			'email'            => $order->get_billing_email(),
			'document'         => ( ! empty( $cpf ) ) ? $cpf : null,
			'company_document' => ( ! empty( $cnpj ) ) ? $cnpj : null,
			'state_register'   => null, // (opcional) (a menos que seja transportadora e logística reversa)
			'address'          => $order->get_shipping_address_1(),
			'complement'       => $order->get_shipping_address_2(),
			'number'           => get_post_meta( $orderId, '_shipping_number', true ),
			'district'         => get_post_meta( $orderId, '_shipping_neighborhood', true ),
			'city'             => $order->get_shipping_city(),
			'state_abbr'       => $order->get_shipping_state(),
			'country_id'       => 'BR',
			'postal_code'      => str_replace( '-', '', $order->get_shipping_postcode() ),
		);
	}

	/**
	 * Function to fetch user balance
	 *
	 * @return json
	 */
	public function getBalance() {

		WpNonceValidatorHelper::check( $_GET['_wpnonce'], 'users' );

		$balance = ( new BalanceService() )->get();

		if ( empty( $balance['success'] ) ) {
			return wp_send_json( $balance, 400 );
		}

		return wp_send_json( $balance, 200 );
	}

	/**
	 * Function to get info by user.
	 *
	 * @return object
	 */
	public function getMe() {

		WpNonceValidatorHelper::check( $_GET['_wpnonce'], 'users' );

		$data = (array) $this->getInfo();

		$data['data']['environment'] = ( ( new OrderQuotationService() )->getEnvironmentToSave() == '_sandbox' )
			? 'Sandbox'
			: 'Produção';

		return wp_send_json( $data['data'], 200 );
	}
}
