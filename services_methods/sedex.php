<?php 

use Services\CalculateShippingMethodService;
use Services\WooCommerceService;

add_action( 'woocommerce_shipping_init', 'sedex_shipping_method_init' );

function sedex_shipping_method_init() {
	if ( ! class_exists( 'WC_Sedex_Shipping_Method' ) ) {

		class WC_Sedex_Shipping_Method extends WC_Shipping_Method {

			public $code = '2';
			/**
			 * Constructor for your shipping class
			 *
			 * @access public
			 * @return void
			 */
			public function __construct($instance_id = 0) {
				$this->id                 = "sedex"; 
				$this->instance_id = absint( $instance_id );
				$this->shipping_class_id = (int) $this->get_option( 'shipping_class_id', '-1' );
				$this->method_title       = "Correios SEDEX (Melhor Envio)";
				$this->method_description = 'Serviço SEDEX';
				$this->enabled            = "yes"; 
				$this->title              = isset($this->settings['title']) ? $this->settings['title'] : 'Melhor Envio SEDEX';
				$this->supports = array(
					'shipping-zones',
					'instance-settings',
					'instance-settings-modal',
				);
				$this->init_form_fields();
				$this->shipping_class_id  = (int) $this->get_option( 'shipping_class_id', '-1' );
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

			/**
			 * calculate_shipping function.
			 *
			 * @access public
			 * @param mixed $package
			 * @return void
			 */
			public function calculate_shipping( $package = []) 
			{
				// Check for shipping classes.
				if ( ! $this->has_only_selected_shipping_class( $package ) ) {
					return;
				}

				$rate = (new CalculateShippingMethodService())->calculate_shipping(
					$package, 
					$this->code,
					'melhorenvio_sedex',
					'Correios'
				);

				if ($rate) {
					$this->add_rate($rate);
				}
			}

			/**
			 * Check if package uses only the selected shipping class.
			 *
			 * @param  array $package Cart package.
			 * @return bool
			 */
			protected function has_only_selected_shipping_class( $package ) {
				
				$only_selected = true;

				if ( -1 === $this->shipping_class_id ) {
					return $only_selected;
				}

				foreach ( $package['contents'] as $item_id => $values ) {
					$product = $values['data'];
					$qty     = $values['quantity'];

					if ( $qty > 0 && $product->needs_shipping() ) {
						if ( $this->shipping_class_id !== $product->get_shipping_class_id() ) {
							$only_selected = false;
							break;
						}
					}
				}

				return $only_selected;
			}
		}
	}
}

function add_sedex_shipping_method( $methods ) {
	$methods['sedex'] = 'WC_Sedex_Shipping_Method';
	return $methods;
}
add_filter( 'woocommerce_shipping_methods', 'add_sedex_shipping_method' );
