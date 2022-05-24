<?php

namespace MelhorEnvio\Services;

use MelhorEnvio\Helpers\FormaterHelper;

class BuyerService {

	const PERSONAL = 1;

	const COMPANY = 2;
	/**
	 * Get data of buyer by order id
	 *
	 * @param int $orderId
	 * @return object $data
	 */
	public function getDataBuyerByOrderId( $orderId ) {
		$order = new \WC_Order( $orderId );

		$cpf   = FormaterHelper::formatDocument(
			get_post_meta(
				$orderId,
				'_billing_cpf',
				true
			)
		);
		$cnpj  = FormaterHelper::formatDocument(
			get_post_meta(
				$orderId,
				'_billing_cnpj',
				true
			)
		);
		$phone = get_post_meta( $orderId, '_billing_cellphone', true );

		if ( empty( $phone ) ) {
			$phone = $order->get_billing_phone();
		}

		$dataBilling  = $this->getBillingAddress( $order );
		$dataShipping = $this->getShippingAddress( $order );

		$typePerson = get_post_meta( $orderId, '_billing_persontype', true );

		if ( empty( $typePerson ) ) {
			$typePerson = self::PERSONAL;
		}

		$nameBuyer = ( ! empty( $order->get_shipping_first_name() ) )
			? sprintf( '%s %s', $order->get_shipping_first_name(), $order->get_shipping_last_name() )
			: sprintf( '%s %s', $order->get_billing_first_name(), $order->get_billing_last_name() );

		$district = ( ! empty( $dataShipping->district ) ) ? $dataShipping->district : $dataBilling->district;

		$body = (object) array(
			'name'           => ( $typePerson == self::COMPANY )
				? $order->get_billing_company()
				: $nameBuyer,
			'phone'          => FormaterHelper::formatPhone( $phone ),
			'phoneMasked'    => $phone,
			'email'          => $order->get_billing_email(),
			'state_register' => null,
			'address'        => ( ! empty( $dataShipping->address ) )
				? $dataShipping->address
				: $dataBilling->address,
			'complement'     => ( ! empty( $dataShipping->complement ) )
				? $dataShipping->complement
				: $dataBilling->complement,
			'number'         => ( ! empty( $dataShipping->number ) )
				? $dataShipping->number
				: $dataBilling->number,
			'district'       => ( ! empty( $district ) ) ? $district : 'N/I',
			'city'           => ( ! empty( $dataShipping->city ) )
				? $dataShipping->city
				: $dataBilling->city,
			'state_abbr'     => ( ! empty( $dataShipping->state_abbr ) )
				? $dataShipping->state_abbr
				: $dataBilling->state_abbr,
			'country_id'     => 'BR',
			'postal_code'    => ( ! empty( $dataShipping->postal_code ) )
				? $dataShipping->postal_code
				: $dataBilling->postal_code,
		);

		if ( $typePerson == self::PERSONAL ) {
			$body->document = $cpf;
		}

		if ( ! empty( $cnpj ) && $typePerson == self::COMPANY ) {
			$body->company_document = $cnpj;
			unset( $body->document );
		}

		return $body;
	}

	/**
	 * Get address billing
	 *
	 * @param post $order
	 * @return object $data
	 */
	public function getBillingAddress( $order ) {
		$orderId = $order->get_id();

		$district = get_post_meta( $orderId, '_billing_neighborhood', true );

		return (object) array(
			'address'     => $order->get_billing_address_1(),
			'complement'  => $order->get_billing_address_2(),
			'number'      => get_post_meta( $orderId, '_billing_number', true ),
			'district'    => ( ! empty( $district ) ) ? $district : 'N/I',
			'city'        => $order->get_billing_city(),
			'state_abbr'  => $order->get_billing_state(),
			'country_id'  => 'BR',
			'postal_code' => str_replace( '-', '', $order->get_billing_postcode() ),
		);
	}

	/**
	 * Get address shipping
	 *
	 * @param post $order
	 * @return object $data
	 */
	public function getShippingAddress( $order ) {
		$orderId = $order->get_id();

		return (object) array(
			'address'     => $order->get_shipping_address_1(),
			'complement'  => $order->get_shipping_address_2(),
			'number'      => get_post_meta( $orderId, '_shipping_number', true ),
			'district'    => get_post_meta( $orderId, '_shipping_neighborhood', true ),
			'city'        => $order->get_shipping_city(),
			'state_abbr'  => $order->get_shipping_state(),
			'country_id'  => 'BR',
			'postal_code' => str_replace( '-', '', $order->get_shipping_postcode() ),
		);
	}
}
