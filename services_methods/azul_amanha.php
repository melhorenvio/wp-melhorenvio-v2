<?php

use Services\CalculateShippingMethodService;

add_action('woocommerce_shipping_init', 'azul_amanha_shipping_method_init');

function azul_amanha_shipping_method_init()
{

    if (!class_exists('WC_Azul_Amanha_Shipping_Method')) {

        class WC_Azul_Amanha_Shipping_Method extends WC_Shipping_Method
        {

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
            public function __construct($instance_id = 0)
            {
                $this->id                 = self::ID;
                $this->instance_id        = absint($instance_id);
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
            function init()
            {
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
            public function calculate_shipping($package = [])
            {
                $rate = (new CalculateShippingMethodService())->calculate_shipping(
                    $package,
                    $this->code,
                    'melhorenvio_azul_amanha',
                    'Azul Cargo'
                );

                if ($rate) {
                    $this->add_rate($rate);
                }
            }
        }
    }
}

function add_azul_amanha_shipping_method($methods)
{
    return $methods;
}

add_filter('woocommerce_shipping_methods', 'add_azul_amanha_shipping_method');
