<?php 

use Controllers\PackageController;
use Controllers\CotationController;
use Controllers\ProductsController;
use Controllers\TimeController;
use Controllers\MoneyController;
use Controllers\OptionsController;
use Models\Cart;
use Models\Quotation;

add_action( 'woocommerce_shipping_init', 'pac_shipping_method_init' );
function pac_shipping_method_init() {
	if ( ! class_exists( 'WC_Pac_Shipping_Method' ) ) {

		class WC_Pac_Shipping_Method extends WC_Shipping_Method {

			public $code = '1';
			/**
			 * Constructor for your shipping class
			 *
			 * @access public
			 * @return void
			 */
			public function __construct($instance_id = 0) {

				$this->init();
				$this->id                 = "pac"; 
				$this->instance_id = absint( $instance_id );
				$this->method_title       = "Correios PAC (Melhor Envio)";
				$this->method_description = 'Serviço PAC';
				$this->enabled            = "yes"; 
				$this->title              = isset($this->settings['title']) ? $this->settings['title'] : 'Melhor Envio PAC';
				$this->supports = array(
					'shipping-zones',
					'instance-settings',
				);
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
							'id' => 'melhorenvio_pac',
							'label' => $method['method'] . (new timeController)->setLabel($result->delivery, $this->code, $result->custom_delivery),
							'cost' => (new MoneyController())->setprice($result->price, $this->code),
							'calc_tax' => 'per_item',
							'meta_data' => [
								'delivery_time' => $result->delivery,
								'company' => 'Correios',
								'name' => $method['method']
							]
						];
						$this->add_rate($rate);
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

function add_pac_shipping_method( $methods ) {
	$methods['pac'] = 'WC_Pac_Shipping_Method';
	return $methods;
}
add_filter( 'woocommerce_shipping_methods', 'add_pac_shipping_method' );

