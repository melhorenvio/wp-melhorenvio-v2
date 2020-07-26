<?php 

use Services\CalculateShippingMethodService;
use Services\WooCommerceService;

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
				$this->method_title       = "Via Brasil Rodoviário (Melhor Envio)";
				$this->method_description = 'Serviço Via Brasil rodoviario';
				$this->enabled            = "yes"; 
				$this->title              = isset($this->settings['title']) ? $this->settings['title'] : 'Melhor Envio Via Brasil Rodoviario';
				$this->supports = array(
					'shipping-zones',
					'instance-settings',
					'instance-settings-modal',
				);
				$this->service = (new CalculateShippingMethodService());
				$this->init_form_fields();
				$this->shipping_class_id  = (int) $this->get_option( 'shipping_class_id', '-1');
			}
			
			/**
			 * Init your settings
			 *
			 * @access public
			 * @return void
			 */
			function init() {
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
			public function calculate_shipping( $package = []) 
			{
				if ( !$this->service->hasOnlySelectedShippingClass( $package, $this->shipping_class_id ) ) {
					return;
				}

				$rate = $this->service->calculate_shipping(
					$package, 
					$this->code,
					'melhorenvio_via_brasil_rodoviario',
					'Via Brasil'
				);

				if ($rate) {
					$this->add_rate($rate);
				}
			}

			/**
			 * Admin options fields.
			 */
			function init_form_fields() {
				$this->instance_form_fields = array(
					'shipping_class_id'  => array(
						'title'       => 'Classe de entrega',
						'type'        => 'select',
						'desc_tip'    => true,
						'default'     => '',
						'class'       => 'wc-enhanced-select',
						'options'     => $this->service->getShippingClassesOptions(),
					),
				);
			}
		}
	}
}

function add_via_brasil_rodoviario_shipping_method( $methods ) {
	$methods['via_brasil_rodoviario'] = 'WC_via_brasil_rodoviario_Shipping_Method';
	return $methods;
}
add_filter( 'woocommerce_shipping_methods', 'add_via_brasil_rodoviario_shipping_method' );

