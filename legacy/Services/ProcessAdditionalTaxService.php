<?php

namespace MelhorEnvio\Services;

use MelhorEnvio\Helpers\SanitizeHelper;

/**
 * Class ProcessAdditionalTaxService
 *
 * @package Services
 */
class ProcessAdditionalTaxService {

	public function init() {
		$shipping_classes = $this->wcGetShippingClasses();
		if ( ! empty( $shipping_classes ) ) {
			$shipping_classes = end( $shipping_classes );
			if ( $shipping_classes->total > 0 ) {
				add_action( 'woocommerce_add_to_cart', array( $this, 'addCart' ) );
				add_action( 'woocommerce_remove_cart_item', array( $this, 'removeCart' ), 10, 2 );
			}
		}
	}

	public function wcGetShippingClasses() {
		global $wpdb;
		return $wpdb->get_results(
			"
            SELECT count(*) as total FROM {$wpdb->prefix}terms as t
            INNER JOIN {$wpdb->prefix}term_taxonomy as tt ON t.term_id = tt.term_id
            WHERE tt.taxonomy LIKE 'product_shipping_class' LIMIT 1
        "
		);
	}

	/**
	 * Function to record the action of inserting the product in the shopping cart
	 * and obtaining the delivery class fee data.
	 *
	 * @return bool
	 */
	public function addCart() {
		$productId = $this->getProductId();

		if ( empty( $productId ) ) {
			return false;
		}

		$dataShipping = ( new ShippingClassDataByProductService() )->get( $productId );

		if ( ! empty( $dataShipping['instance_id'] ) ) {
			( new AdditionalQuotationService() )->register(
				$productId,
				$dataShipping['instance_id'],
				$dataShipping['additional_tax'],
				$dataShipping['additional_time'],
				$dataShipping['percent_tax']
			);
		}
	}

	/**
	 * @return int|null
	 */
	private function getProductId() {
		if ( empty( $_POST['product_id'] ) && empty( $_POST['add-to-cart'] ) ) {
			return false;
		}

		return ( ! empty( $_POST['product_id'] ) )
			? SanitizeHelper::apply( $_POST['product_id'] )
			: SanitizeHelper::apply( $_POST['add-to-cart'] );
	}

	/**
	 * Function to remove data from delivery classes when the product is removed
	 * from the shopping cart.
	 *
	 * @param $cart_item_key
	 * @param $cart
	 */
	public function removeCart( $cart_item_key, $cart ) {
		foreach ( $cart->cart_contents as $key => $item ) {
			if ( $key === $cart_item_key ) {
				( new AdditionalQuotationService() )
					->removeItem( $item['product_id'] );
			}
		}
	}
}
