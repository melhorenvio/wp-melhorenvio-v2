<?php 

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

    add_action( 'woocommerce_shipping_init', 'jadlog_com_shipping_method_init' );
	function jadlog_com_shipping_method_init() {
		if ( ! class_exists( 'WC_Jadlog_com_Shipping_Method' ) ) {

			class WC_Jadlog_com_Shipping_Method extends WC_Shipping_Method {

                protected $code = '4';
				/**
				 * Constructor for your shipping class
				 *
				 * @access public
				 * @return void
				 */
				public function __construct($instance_id = 0) {
					
					$this->id  = "jadlog_com"; 
					$this->instance_id = absint( $instance_id );
                    $this->method_title       = "Jadlog Com (Melhor envio)"; 
					$this->method_description = ".Com, serviço de entregas expressas da Jadlog";
					$this->enabled            = "yes"; 
					$this->title              = (get_option('woocommerce_jadlog_com_title_custom_shipping')) ? get_option('woocommerce_jadlog_com_title_custom_shipping') : "Jadlog Com";
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
				 * Initialise Gateway Settings Form Fields
				 */
				function init_form_fields() {

					$this->form_fields = array(
						'custom_shipping' => array(
							'title' => 'shipping',
							'type' => 'hidden',
							'default' => 'jadlog_com'
						),
						'title_custom_shipping' => array(
							'title' => 'Título',
							'description' => 'Nome do serviço exibido para o cliente',
							'desc_tip'    => true,
							'type' => 'text',
							'default' => (get_option('woocommerce_jadlog_com_title_custom_shipping')) ? get_option('woocommerce_jadlog_com_title_custom_shipping') : "Jadlog Com"
						),
						'ee_custom_shipping' => array(
							'title'   => 'Tempo de entrega',
							'type'    => 'checkbox',
							'description' => 'Exibir o tempo estimado de entrega em dias úteis',
							'desc_tip'    => true,
							'label'   => 'Exibir estimativa de entrega',
							'default' => (get_option('woocommerce_jadlog_com_ee_custom_shipping')) ? get_option('woocommerce_jadlog_com_ee_custom_shipping') : "no"
						),
						'days_extra_custom_shipping' => array(
							'title'   => 'Dias extras',
							'type'    => 'number',
							'description' => 'Adiciona dias na estimativa na entrega',
							'desc_tip'    => true,
							'label'   => 'Dias a mais a serem adicionados na exibição do Prazo de Entrega.',
							'default' => (get_option('woocommerce_jadlog_com_days_extra_custom_shipping')) ? get_option('woocommerce_jadlog_com_days_extra_custom_shipping') : 0
						),
						'pl_custom_shipping' => array(
							'title'   => 'Taxa de manuseio',
							'type'    => 'price',
							'description' => 'Digite um valor, por exemplo: 2.50, ou uma porcentagem, por exemplo, 5%. Deixe em branco para desabilitar',
							'desc_tip'    => true,
							'label'   => 'Porcentagem de lucro',
							'default' => (get_option('woocommerce_jadlog_com_pl_custom_shipping')) ? get_option('woocommerce_jadlog_com_pl_custom_shipping') : 0
						),
						'shipping_class' => array(
							'title'       => 'Classe de entrega',
							'type'        => 'select',
							'desc_tip'    => true,
							'default'     => get_class_option_jadlog_com(),
							'class'       => 'wc-enhanced-select',
							'options'     => get_shipping_classes_options(),
						)
					);
				} 

				/**'
				 * calculate_shipping function.
				 *
				 * @access public
				 * @param mixed $package
				 * @return void
				 */
				public function calculate_shipping( $package = []) {

					$ar = false;
					$mp = false;

                    $result = wpmelhorenviopackage_getPackageInternal($package, $this->code, $ar, $mp);

                    if ($result != null && $result->price > 0) {

                        $rate = array(
                            'id'       => "wpmelhorenvio_".$result->company->name."_".$result->name,
                            'label'    => $this->title . calculate_delivery_time_jadlog_com($result->delivery_range),
                            'cost'     => calcute_value_shipping_jadlog_com($result->price),
							'calc_tax' => 'per_item',
							'meta_data' => [
								'delivery_time' => $result->delivery_time,
								'company' => $result->company->name
							]
                        );
                        $this->add_rate( $rate );
                    }
                    
                }  
			}
		}
	}

	function get_use_ar_jadlog_com() {
		$ar = get_option('woocommerce_jadlog_com_ar_custom_shipping');

		if (!$ar) {
			return false;
		}

		if($ar == 'yes') {
			return true;
		}
		
		return false;
	}

	function get_use_mp_jadlog_com() {
		$mp = get_option('woocommerce_jadlog_com_mp_custom_shipping');

		if (!$mp) {
			return false;
		}

		if($mp == 'yes') {
			return true;
		}
		
		return false;
	}

	function get_class_option_jadlog_com() {

		$co = get_option('woocommerce_jadlog_com_class_option');
		if (!$co) {
			return '';
		}
		return $co;
	}

	function update_option_value_jadlog_com($key, $value) {
		$option = get_option($key);
		if ($option === false) {
			return  add_option($key, $value, true);
		}
		return update_option($key, $value, true);
	}

	function calculate_delivery_time_jadlog_com($delivery_range) {

		$days_extras = intval(get_option('woocommerce_jadlog_com_days_extra_custom_shipping'));	

		$time = '';
		if (get_option('woocommerce_jadlog_com_ee_custom_shipping') == 'yes') {
			$days_extras = intval(get_option('woocommerce_jadlog_com_days_extra_custom_shipping'));

			if ($delivery_range->min == $delivery_range->max) {
				$time = ' (' . ($delivery_range->max + $days_extras) . ' dias)';
				if ($delivery_range->max + $days_extras == 1) {
					$time = '(1 dia)';
				}
			}

			if ($delivery_range->min < $delivery_range->max) {
				$time = ' (' . ($delivery_range->min + $days_extras) . ' à ' . ($delivery_range->max + $days_extras) . ' dias)';
			}

		}

		return $time;
	}

	function calcute_value_shipping_jadlog_com($price) {

		$price = floatval($price);
		$valueExtra = get_option('woocommerce_jadlog_com_pl_custom_shipping');

		$pos = strpos($valueExtra, '%');
		if ($pos) {
			$percent = ($price / 100 ) * floatval($valueExtra);
			return $percent + $price;
		}
		$valueExtra = floatval($valueExtra);
		return $price + $valueExtra;
	}
	
	function add_jadlog_com_shipping_method( $methods ) {
		$methods['jadlog_com'] = 'WC_Jadlog_com_Shipping_Method';
		return $methods;
	}
	add_filter( 'woocommerce_shipping_methods', 'add_jadlog_com_shipping_method' );

	// UPDATE OPTION 
	if (isset($_GET['page']) && $_GET['page'] == 'wc-settings' && isset($_GET['tab']) && $_GET['tab'] == 'shipping' && isset($_GET['instance_id']) ) {

		if (isset($_POST['woocommerce_jadlog_com_shipping_class'])) {
			update_option_value_jadlog_com('woocommerce_jadlog_com_class_option', $_POST['woocommerce_jadlog_com_shipping_class']);
		}

		if (isset($_POST['woocommerce_jadlog_com_title_custom_shipping'])) {
			update_option_value_jadlog_com('woocommerce_jadlog_com_title_custom_shipping', $_POST['woocommerce_jadlog_com_title_custom_shipping']);
		}

		if (isset($_POST['woocommerce_jadlog_com_days_extra_custom_shipping'])) {
			update_option_value_jadlog_com('woocommerce_jadlog_com_days_extra_custom_shipping', $_POST['woocommerce_jadlog_com_days_extra_custom_shipping']);
		}

		if (isset($_POST['woocommerce_jadlog_com_pl_custom_shipping'])) {
			update_option_value_jadlog_com('woocommerce_jadlog_com_pl_custom_shipping', $_POST['woocommerce_jadlog_com_pl_custom_shipping']);
		}

		if (isset($_POST['woocommerce_jadlog_com_custom_shipping']) && $_POST['woocommerce_jadlog_com_custom_shipping'] == 'jadlog_com') {
			update_option_value_jadlog_com('woocommerce_jadlog_com_ar_custom_shipping', "no");
			if (isset($_POST['woocommerce_jadlog_com_ar_custom_shipping'])) {
				update_option_value_jadlog_com('woocommerce_jadlog_com_ar_custom_shipping', "yes");
			}
		}

		if (isset($_POST['woocommerce_jadlog_com_custom_shipping']) && $_POST['woocommerce_jadlog_com_custom_shipping'] == 'jadlog_com') {
			update_option_value_jadlog_com('woocommerce_jadlog_com_vd_custom_shipping', "no");
			if (isset($_POST['woocommerce_jadlog_com_vd_custom_shipping'])) {
				update_option_value_jadlog_com('woocommerce_jadlog_com_vd_custom_shipping', "yes");
			}
		}

		if (isset($_POST['woocommerce_jadlog_com_custom_shipping']) && $_POST['woocommerce_jadlog_com_custom_shipping'] == 'jadlog_com') {
			update_option_value_jadlog_com('woocommerce_jadlog_com_ee_custom_shipping', "no");
			if (isset($_POST['woocommerce_jadlog_com_ee_custom_shipping'])) {
				update_option_value_jadlog_com('woocommerce_jadlog_com_ee_custom_shipping', "yes");
			}
		}

		if (isset($_POST['woocommerce_jadlog_com_custom_shipping']) && $_POST['woocommerce_jadlog_com_custom_shipping'] == 'jadlog_com') {
			update_option_value_jadlog_com('woocommerce_jadlog_com_mp_custom_shipping', "no");
			if (isset($_POST['woocommerce_jadlog_com_mp_custom_shipping'])) {
				update_option_value_jadlog_com('woocommerce_jadlog_com_mp_custom_shipping', "yes");
			}
		}
	}
}