<?php 

use Services\CalculateShippingMethodService;
use Services\WooCommerceService;

add_action( 'woocommerce_shipping_init', 'azul_ecommerce_shipping_method_init' );

function azul_ecommerce_shipping_method_init() {

	if ( ! class_exists( 'WC_Azul_Ecommerce_Shipping_Method' ) ) {

		class WC_Azul_Ecommerce_Shipping_Method extends WC_Shipping_Method {

			public $code = '16';

			const ID = 'azul_ecommerce';

			const METHOD_TITLE = "Azul Ecommerce (Melhor Envio)";

			const METHOD_DESCRIPTION = 'Serviço Azul Cargo Ecommerce';

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
					'instance-settings-modal',
				);
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
				$rate = (new CalculateShippingMethodService())->calculate_shipping(
					$package, 
					$this->code,
					'melhorenvio_azul_ecommerce',
					'Azul Cargo'
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
						'options'     => $this->get_shipping_classes_options(),
					),
				);
			}
			/**
			 * Get shipping classes options.
			 *
			 * @return array
			 */
			protected function get_shipping_classes_options() {
				$shipping_classes = WC()->shipping->get_shipping_classes();
				$options          = array(
					'-1' => 'Qualquer classe de entrega',
					'0'  => 'Sem classe de entrega',
				);

				if ( ! empty( $shipping_classes ) ) {
					$options += wp_list_pluck( $shipping_classes, 'name', 'term_id' );
				}

				return $options;
			}
		}
	}
}

function add_azul_ecommerce_shipping_method( $methods ) {
	return $methods;
}

add_filter( 'woocommerce_shipping_methods', 'add_azul_ecommerce_shipping_method' );