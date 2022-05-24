<?php

namespace MelhorEnvio\Services;

class ShippingClassService {

	protected $maxTaxExtra = 0;

	protected $maxTimeExtra = 0;

	protected $maxPercentExtra = 0;

	protected $shippingClassesId = array();

	protected $shippingClasses = array();

	/**
	 * @param object $package
	 * @return array
	 */
	public function getExtrasOnCart() {
		$totalShippingClasses = $this->getCountShippingClasses();

		if ( $totalShippingClasses == 0 ) {
			return $this->normalizeArray();
		}

		$this->setShippingClassesId();

		if ( empty( $this->shippingClassesId ) ) {
			return $this->normalizeArray();
		}

		$this->setExtraTax();

		$this->filterMaxValue();

		return $this->normalizeArray();
	}

	private function setShippingClassesId() {
		global $woocommerce;

		$dataCart = $woocommerce->cart->get_cart();

		if ( ! empty( $dataCart ) ) {
			foreach ( $dataCart as $itemCart ) {
				$shippingClassId = $itemCart['data']->get_shipping_class_id();
				if ( ! empty( $shippingClassId ) ) {
					$this->shippingClassesId[] = $shippingClassId;
				}
			}
		}
	}

	/**
	 * @return int
	 */
	public function getCountShippingClasses() {
		global $wpdb;

		$result = $wpdb->get_results(
			"
            SELECT count(*) as total FROM {$wpdb->prefix}terms as t
            INNER JOIN {$wpdb->prefix}term_taxonomy as tt ON t.term_id = tt.term_id
            WHERE tt.taxonomy LIKE 'product_shipping_class' LIMIT 1
        "
		);

		return end( $result )->total;
	}

	public function setExtraTax() {
		global $woocommerce;

		$shipping_packages = $woocommerce->cart->get_shipping_packages();

		$deliveryZones = wc_get_shipping_zone( reset( $shipping_packages ) );

		$methods = $deliveryZones->get_shipping_methods();

		if ( ! empty( $methods ) ) {
			foreach ( $methods as $method ) {
				if ( $this->isValidToAdd( $method ) ) {
					$this->shippingClasses[ $method->instance_settings['shipping_class_id'] ] = array(
						'additional_tax'  => floatval( $method->instance_settings['additional_tax'] ),
						'additional_time' => floatval( $method->instance_settings['additional_time'] ),
						'percent_tax'     => floatval( $method->instance_settings['percent_tax'] ),
					);
				}
			}
		}
	}

	/**
	 * @param array $method
	 * @return bool
	 */
	private function isValidToAdd( $method ) {
		if ( ! isset( $method->instance_settings['shipping_class_id'] ) ) {
			return false;
		}

		return in_array( $method->instance_settings['shipping_class_id'], $this->shippingClassesId ) &&
		$method->instance_settings['shipping_class_id'] != CalculateShippingMethodService::ANY_DELIVERY;
	}

	/**
	 * @return array
	 */
	public function filterMaxValue() {
		if ( ! empty( $this->shippingClasses ) ) {
			foreach ( $this->shippingClasses as $data ) {
				if ( $data['additional_tax'] > $this->maxTaxExtra ) {
					$this->maxTaxExtra = $data['additional_tax'];
				}

				if ( $data['additional_time'] > $this->maxTimeExtra ) {
					$this->maxTimeExtra = $data['additional_time'];
				}

				if ( $data['percent_tax'] > $this->maxPercentExtra ) {
					$this->maxPercentExtra = $data['percent_tax'];
				}
			}
		}
	}

	/**
	 * @return array
	 */
	private function normalizeArray() {
		return array(
			'taxExtra'  => $this->maxTaxExtra,
			'timeExtra' => $this->maxTimeExtra,
			'percent'   => $this->maxPercentExtra,
		);
	}
}
