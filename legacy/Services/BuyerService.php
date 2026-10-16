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

		$cpf = FormaterHelper::formatDocument(
			$order->get_meta('_billing_cpf')
		);

		$cnpj = FormaterHelper::formatDocument(
			$order->get_meta('_billing_cnpj')
		);

		$phone = get_post_meta( $orderId, '_billing_cellphone', true );

		if ( empty( $phone ) ) {
			$phone = $order->get_billing_phone();
		}

		$dataBilling  = $this->getBillingAddress( $order );
		$dataShipping = $this->getShippingAddress( $order );

		$typePerson = $order->get_meta('_billing_persontype');

		if ( empty( $typePerson ) ) {
			$typePerson = self::PERSONAL;
		}

		$nameBuyer = ( ! empty( $order->get_shipping_first_name() ) )
			? sprintf( '%s %s', $order->get_shipping_first_name(), $order->get_shipping_last_name() )
			: sprintf( '%s %s', $order->get_billing_first_name(), $order->get_billing_last_name() );

		$district = $dataShipping->district;

		$body = (object) array(
			'name'           => ( $typePerson == self::COMPANY )
				? $order->get_billing_company()
				: $nameBuyer,
			'phone'          => FormaterHelper::formatPhone( $phone ),
			'phoneMasked'    => $phone,
			'email'          => $order->get_billing_email(),
			'state_register' => null,
			'address'        => $dataShipping->address,
			'complement'     => $dataShipping->complement,
			'number'         => $dataShipping->number,
			'district'       => ( ! empty( $district ) ) ? $district : 'N/I',
			'city'           => $dataShipping->city,
			'state_abbr'     => $dataShipping->state_abbr,
			'country_id'     => 'BR',
			'postal_code'    => $dataShipping->postal_code
		);

		if ( $typePerson == self::PERSONAL ) {
			$body->document = $cpf;
		}

		if ( ! empty( $cnpj ) && $typePerson == self::COMPANY ) {
			$body->company_document = $cnpj;
			unset( $body->document );
		}

		if (empty($body->document) && !empty($cnpj)) {
			$body->company_document = $cnpj;
			unset($body->company);
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

		$district = $order->get_meta('_shipping_neighborhood');

		return (object) array(
			'address'     => $order->get_billing_address_1(),
			'complement'  => $order->get_billing_address_2(),
			'number'      => $order->get_meta('_shipping_number'),
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
		
		return (object) array(
			'address'     => $order->get_shipping_address_1(),
			'complement'  => $order->get_shipping_address_2(),
			'number'      => $order->get_meta('_shipping_number'),
			'district'    => $order->get_meta('_shipping_neighborhood'),
			'city'        => $order->get_shipping_city(),
			'state_abbr'  => $order->get_shipping_state(),
			'country_id'  => 'BR',
			'postal_code' => str_replace( '-', '', $order->get_shipping_postcode() ),
		);
	}
}
