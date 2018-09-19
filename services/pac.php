<?php 

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

    add_action( 'woocommerce_shipping_init', 'pac_shipping_method_init' );
	function pac_shipping_method_init() {
		if ( ! class_exists( 'WC_Pac_Shipping_Method' ) ) {

			class WC_Pac_Shipping_Method extends WC_Shipping_Method {

                protected $code = '1';
				/**
				 * Constructor for your shipping class
				 *
				 * @access public
				 * @return void
				 */
				public function __construct($instance_id = 0) {
					$this->id                 = "pac"; 
                    $this->instance_id = absint( $instance_id );
                    $this->method_title       = "Correios Pac (Melhor envio)"; 
					$this->method_description = 'Serviço Pac';
					$this->enabled            = "yes"; 
					$this->title              = (get_option('woocommerce_pac_title_custom_shipping')) ? get_option('woocommerce_pac_title_custom_shipping') : "Correios Pac";
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
				}

				/**
				 * calculate_shipping function.
				 *
				 * @access public
				 * @param mixed $package
				 * @return void
				 */
				public function calculate_shipping( $package = []) {
					
					$rate = [
						'id' => 'melhorenvio_pac',
						'label' => 'PAC',
						'cost' => 24.90,
						'calc_tax' => 'per_item',
						'meta_data' => [
							'delivery_time' => 6,
							'company' => 'Correios'
						]
					]; 

					$this->add_rate($rate);
                    
                }
                
                /**
				 * Initialise Gateway Settings Form Fields
				 */
				function init_form_fields() {

					$this->form_fields = array(
						'custom_shipping' => array(
							'title' => 'shipping',
							'type' => 'hidden',
							'default' => 'pac'
						),
						'title_custom_shipping' => array(
							'title' => 'Título',
							'description' => 'Nome do serviço exibido para o cliente',
							'desc_tip'    => true,
							'type' => 'text',
							'default' => (get_option('woocommerce_pac_title_custom_shipping')) ? get_option('woocommerce_pac_title_custom_shipping') : "Pac"
						),
						'ee_custom_shipping' => array(
							'title'   => 'Tempo de entrega',
							'type'    => 'checkbox',
							'description' => 'Exibir o tempo estimado de entrega em dias úteis',
							'desc_tip'    => true,
							'label'   => 'Exibir estimativa de entrega',
							'default' => (get_option('woocommerce_pac_ee_custom_shipping')) ? get_option('woocommerce_pac_ee_custom_shipping') : "no"
						),
						'days_extra_custom_shipping' => array(
							'title'   => 'Dias extras',
							'type'    => 'number',
							'description' => 'Adiciona dias na estimativa na entrega',
							'desc_tip'    => true,
							'label'   => 'Dias a mais a serem adicionados na exibição do Prazo de Entrega.',
							'default' => (get_option('woocommerce_pac_days_extra_custom_shipping')) ? get_option('woocommerce_pac_days_extra_custom_shipping') : 0
						),
						'pl_custom_shipping' => array(
							'title'   => 'Taxa de manuseio',
							'type'    => 'price',
							'description' => 'Digite um valor, por exemplo: 2.50, ou uma porcentagem, por exemplo, 5%. Deixe em branco para desabilitar',
							'desc_tip'    => true,
							'label'   => 'Porcentagem de lucro',
							'default' => (get_option('woocommerce_pac_pl_custom_shipping')) ? get_option('woocommerce_pac_pl_custom_shipping') : 0
						),
					);
				}   
			}
		}
	}
	
	function add_pac_shipping_method( $methods ) {
		$methods['pac'] = 'WC_Pac_Shipping_Method';
		return $methods;
	}
	add_filter( 'woocommerce_shipping_methods', 'add_pac_shipping_method' );

}
