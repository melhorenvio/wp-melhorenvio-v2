<?php

namespace MelhorEnvio\Services;

class WooCommerceService {

	public function hasFreeShippingMethod() {
		$totalCart = 0;

		$freeShiping = false;

		foreach ( WC()->cart->cart_contents as $cart ) {
			$totalCart += $cart['line_subtotal'];
		}

		foreach ( WC()->cart->get_coupons() as $cp ) {
			if ( $cp->get_free_shipping() && $totalCart >= $cp->amount ) {
				$freeShiping = true;
			}
		}

		if ( $freeShiping ) {
			return array(
				'id'        => 'free_shipping',
				'label'     => 'Frete grátis',
				'cost'      => 0,
				'calc_tax'  => 'per_item',
				'meta_data' => array(
					'delivery_time' => '',
					'company'       => '',
				),
			);
		}

		return false;
	}
}
