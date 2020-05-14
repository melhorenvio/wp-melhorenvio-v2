<?php 

use Helpers\OptionsHelper;
use Helpers\TimeHelper;
use Helpers\MoneyHelper;
use Models\Cart;
use Models\Quotation;
use Services\CartWooCommerceService;
use Services\QuotationService;
use Services\WooCommerceService;

add_action( 'woocommerce_shipping_init', 'azul_amanha_shipping_method_init' );

function azul_amanha_shipping_method_init() {

	if ( ! class_exists( 'WC_Azul_Amanha_Shipping_Method' ) ) {

		class WC_Azul_Amanha_Shipping_Method extends WC_Shipping_Method {

			public $code = '15';

			const ID = 'azul_amanha';

			const METHOD_TITLE = "Azul Amanhã (Melhor Envio)";

			const METHOD_DESCRIPTION = 'Serviço Azul Cargo Amanhã';

			/**
			 * Constructor for your shipping class
			 *
			 * @access public
			 * @return void
			 */
			public function __construct($instance_id = 0) {
				$this->id                 = self::ID; 
				$this->instance_id        = absint( $instance_id );
				$this->method_title       = self::METHOD_TITLE;
				$this->method_description = self::METHOD_DESCRIPTION;
				$this->enabled            = "yes"; 
				$this->title              = isset($this->settings['title']) ? $this->settings['title'] : self::METHOD_TITLE;
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

				$products = (isset($package['cotationProduct'])) ? $package['cotationProduct'] : (new CartWooCommerceService())->getProducts();

				$result = (new QuotationService())->calculateQuotationByProducts($products, $to, $this->code);

				if ($result) {

					if (isset($result->name) && isset($result->price)) {

						$method = (new optionsHelper())->getName($result->id, $result->name, null, null);

						$rate = [
							'id' => 'melhorenvio_azul_amanha',
							'label' => $method['method'] . (new timeHelper)->setLabel($result->delivery_range, $this->code, $result->custom_delivery_range),
							'cost' => (new MoneyHelper())->setprice($result->price, $this->code),
							'calc_tax' => 'per_item',
							'meta_data' => [
								'delivery_time' => $result->delivery_range,
								'company' => 'Azul Cargo',
								'name' => $method['method']
							]
						];
						$this->add_rate($rate);
					}

					$freeShiping = (new WooCommerceService())->hasFreeShippingMethod();
                    if ($freeShiping != false) {
                        $this->add_rate($freeShiping);
                    }
				} 
			}
		}
	}
}

function add_azul_amanha_shipping_method( $methods ) {
	$methods['azul_amanha'] = 'WC_Azul_Amanha_Shipping_Method';
	return $methods;
}

add_filter( 'woocommerce_shipping_methods', 'add_azul_amanha_shipping_method' );

function azul_amanha_validate_order($posted) {

}

add_action('woocommerce_review_order_before_cart_contents', 'azul_amanha_validate_order', 10);
add_action('woocommerce_after_checkout_validation', 'azul_amanha_validate_order', 10);
