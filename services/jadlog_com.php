<?php 

use Controllers\PackageController;
use Controllers\CotationController;
use Controllers\ProductsController;
use Controllers\TimeController;
use Controllers\MoneyController;
use Controllers\OptionsController;
use Models\Cart;
use Models\Quotation;

add_action( 'woocommerce_shipping_init', 'jadlog_com_shipping_method_init' );
function jadlog_com_shipping_method_init() {
	if ( ! class_exists( 'WC_Jadlog_Com_Shipping_Method' ) ) {

		class WC_Jadlog_Com_Shipping_Method extends WC_Shipping_Method {

			public $code = '4';
			/**
			 * Constructor for your shipping class
			 *
			 * @access public
			 * @return void
			 */
			public function __construct($instance_id = 0) {
				$this->id                 = "jadlog_com"; 
				$this->instance_id = absint( $instance_id );
				$this->method_title       = "Jadlog .Com (Melhor Envio)";
				$this->method_description = 'Serviço Jadlog .Com';
				$this->enabled            = "yes"; 
				$this->title              = isset($this->settings['title']) ? $this->settings['title'] : 'Melhor Envio Jadlog .Com';
				$this->supports = array(
					'shipping-zones',
					'instance-settings',
				);
				$this->init_form_fields();
			}
			
			/**
			 * Init your settings
			 *
			 * @access public
			 * @return void
			 */
			function init() {
				$this->init_form_fields(); 
				$this->init_settings(); 
				add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
			}

			/**
			 * calculate_shipping function.
			 *
			 * @access public
			 * @param mixed $package
			 * @return void
			 */
			public function calculate_shipping( $package = []) {

				global $woocommerce;
				
				$to = str_replace('-', '', $package['destination']['postcode']);

				$products = (isset($package['cotationProduct'])) ? $package['cotationProduct'] : (new Cart())->getProductsOnCart();

				$result = (new Quotation(null, $products, $package, $to))->calculate($this->code);
				
				if ($result) {

					if (isset($result->name) && isset($result->price)) {

						$method = (new optionsController())->getName($result->id, $result->name, null, null);

						$rate = [
							'id' => 'melhorenvio_jadlog_com',
							'label' => $method['method'] . (new timeController)->setLabel($result->delivery, $this->code, $result->custom_delivery),
							'cost' => (new MoneyController())->setprice($result->price, $this->code),
							'calc_tax' => 'per_item',
							'meta_data' => [
								'delivery_time' => $result->delivery,
								'company' => 'Jadlog',
								'name' => $method['method']
							]
						];
						$this->add_rate($rate);
					}

					$freeShiping = (new CotationController())->freeShipping();
					if ($freeShiping != false) {
						$this->add_rate($freeShiping);
					}

				} 

				$freeShiping = (new CotationController())->freeShipping();
				if ($freeShiping != false) {
					$this->add_rate($freeShiping);
				}
			}
		}
	}
}

function add_jadlog_com_shipping_method( $methods ) {
	$methods['jadlog_com'] = 'WC_Jadlog_Com_Shipping_Method';
	return $methods;
}
add_filter( 'woocommerce_shipping_methods', 'add_jadlog_com_shipping_method' );

function jadlog_com_validate_order($posted) {

}

add_action('woocommerce_review_order_before_cart_contents', 'jadlog_com_validate_order', 10);
add_action('woocommerce_after_checkout_validation', 'jadlog_com_validate_order', 10);
