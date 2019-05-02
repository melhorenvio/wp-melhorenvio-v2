<?php 

use Controllers\PackageController;
use Controllers\CotationController;
use Controllers\ProductsController;
use Controllers\TimeController;
use Controllers\MoneyController;
use Controllers\OptionsController;

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

    add_action( 'woocommerce_shipping_init', 'via_brasil_rodoviario_shipping_method_init' );
	function via_brasil_rodoviario_shipping_method_init() {
		if ( ! class_exists( 'WC_via_brasil_rodoviario_Shipping_Method' ) ) {

			class WC_Via_Brasil_Rodoviario_Shipping_Method extends WC_Shipping_Method {

                public $code = '9';
				/**
				 * Constructor for your shipping class
				 *
				 * @access public
				 * @return void
				 */
				public function __construct($instance_id = 0) {
					$this->id                 = "via_brasil_rodoviario"; 
                    $this->instance_id = absint( $instance_id );
                    $this->method_title       = "Via Brasil Rodoviário (Melhor envio)"; 
					$this->method_description = 'Serviço Via Brasil rodoviario';
					$this->enabled            = "yes"; 
					$this->title              = isset($this->settings['title']) ? $this->settings['title'] : 'Melhor Envio Via Brasil Rodoviario';
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
					
					if(self::freeShipping()) {
						return;
					}
					
					global $woocommerce;
					
					$to = str_replace('-', '', $package['destination']['postcode']);

					$products = (isset($package['cotationProduct'])) ? $package['cotationProduct'] : (new ProductsController())->getProductsCart();

					$result = (new CotationController())->makeCotationProducts($products, [$this->code], $to, [], false);
					
					if ($result) {

						if (isset($result->name) && isset($result->price)) {

							$method = (new optionsController())->getName($result->id, $result->name, null, null);

							$rate = [
								'id' => 'melhorenvio_via_brasil_rodoviario',
								'label' => $method['method'] . (new timeController)->setLabel($result->delivery, $this->code),
								'cost' => (new MoneyController())->setprice($result->price, $this->code),
								'calc_tax' => 'per_item',
								'meta_data' => [
									'delivery_time' => $result->delivery,
									'company' => 'Via Brasil',
									'name' => $method['method']
								]
							]; 
							$this->add_rate($rate);
						}
					}
				}
				
				function freeShipping() {

					global $woocommerce;

					$totalCart = 0;
					$freeShiping = false;
					foreach(WC()->cart->cart_contents as $cart) {
						$totalCart += $cart['line_subtotal'];
					}

					foreach(WC()->cart->get_coupons() as $cp) {
						if ($cp->discount_type == 'fixed_cart' && $totalCart >= $cp->amount ) {
							$freeShiping = true;
						}
					}

					if ($freeShiping) {
						$rate = [
							'id' => 'free_shipping',
							'label' => 'Frete grátis',
							'cost' => '',
							'calc_tax' => 'per_item',
							'meta_data' => [
								'delivery_time' => '',
								'company' => ''
							]
						];

						$this->add_rate($rate);
						return true;
					}
					return false;
				}
                
                /**
				 * Initialise Gateway Settings Form Fields
				 */
				function init_form_fields() {

					$this->form_fields = [
						'title' => [
							'title' => 'Titulo',
							'type' => 'text',
							'default' => 'Via Brasil Rodoviario'
						],
						'enabled' => [
							'title' => 'Ativar',
							'type' => 'checkbox',
							'default' => 'yes'
						],
					];
				}   
			}
		}
	}
	
	function add_via_brasil_rodoviario_shipping_method( $methods ) {
		$methods['via_brasil_rodoviario'] = 'WC_via_brasil_rodoviario_Shipping_Method';
		return $methods;
	}
	add_filter( 'woocommerce_shipping_methods', 'add_via_brasil_rodoviario_shipping_method' );
}
