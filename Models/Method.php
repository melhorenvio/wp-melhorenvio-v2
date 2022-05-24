<?php

namespace MelhorEnvio\Models;

use MelhorEnvio\Services\OptionsMethodShippingService;

class Method {

	public function getMethodShipmentSelected( $order_id ) {
		global $wpdb;

		$sql = sprintf(
			'
            select 
                meta_value as method 
            from 
                %swoocommerce_order_itemmeta 
            where 
                meta_key = "method_id" and 
                order_item_id IN (
                    select 
                        order_item_id 
                    from 
                        %swoocommerce_order_items where order_id = %d and 
                        order_item_type = "shipping"
                    ) ',
			$wpdb->prefix,
			$wpdb->prefix,
			$order_id
		);

		$result = $wpdb->get_results( $sql );

		$result = end( $result );

		return $this->getCodeMelhorEnvioShippingMethod( $result->method );
	}

	/**
	 * @return void
	 */
	public function getCodeMelhorEnvioShippingMethod( $method_id ) {
		$shipping_methods = \WC()->shipping->get_shipping_methods();

		foreach ( $shipping_methods as $method ) {

			if ( $method_id == $method->id ) {

				if ( isset( $method->code ) ) {
					return $method->code;
				}
				return null;
			}
		}

		return null;
	}

	public function getArrayShippingMethodsEnabledByZoneMelhorEnvio() {
		global $wpdb;
		$enableds = array();
		$sql      = sprintf( 'select * from %swoocommerce_shipping_zone_methods where is_enabled = 1', $wpdb->prefix );
		$results  = $wpdb->get_results( $sql );

		foreach ( $results as $item ) {
			$enableds[] = $item->method_id;
		}

		return $enableds;
	}
}
