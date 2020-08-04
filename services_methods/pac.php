<?php

use Services\CalculateShippingMethodService;

add_action('woocommerce_shipping_init', 'pac_shipping_method_init');
function pac_shipping_method_init()
{
    if (!class_exists('WC_Pac_Shipping_Method')) {

        class WC_Pac_Shipping_Method extends WC_Shipping_Method
        {
            const CODE = '1';

            const METHOD_TITLE = "Correios Pac (Melhor Envio)";

            const ID = 'melhorenvio_pac';

            const METHOD_DESCRIPTION = 'Serviço Correios Pac';

            const COMPANY = 'Correios';

            /**
             * Constructor for your shipping class
             *
             * @access public
             * @return void
             */
            public function __construct($instance_id = 0)
            {
                $this->init();
                $this->id = self::ID;
                $this->instance_id = absint($instance_id);
                $this->method_title = self::METHOD_TITLE;
                $this->method_description = self::METHOD_DESCRIPTION;
                $this->enabled = "yes";
                $this->title = !empty($this->settings['title'])
                    ? $this->settings['title']
                    : self::METHOD_TITLE;
                $this->supports = array(
                    'shipping-zones',
                    'instance-settings',
                    'instance-settings-modal',
                );
                $this->service = new CalculateShippingMethodService();
                $this->init_form_fields();
                $this->shipping_class_id  = (int) $this->get_option(
                    'shipping_class_id',
                    CalculateShippingMethodService::ANY_DELIVERY
                );
            }

            /**
             * Init your settings
             *
             * @access public
             * @return void
             */
            function init()
            {
                $this->init_settings();
                add_action(
                    'woocommerce_update_options_shipping_' . $this->id,
                    array($this, 'process_admin_options')
                );
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
                if (!$this->service->hasOnlySelectedShippingClass($package, $this->shipping_class_id)) {
                    return;
                }

                $rate = $this->service->calculate_shipping(
                    $package,
                    self::CODE,
                    self::ID,
                    self::COMPANY
                );

                if ($rate) {
                    $this->add_rate($rate);
                }
            }

            /**
             * Admin options fields.
             */
            function init_form_fields()
            {
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

function add_pac_shipping_method($methods)
{
    $methods['pac'] = 'WC_Pac_Shipping_Method';
    return $methods;
}
add_filter('woocommerce_shipping_methods', 'add_pac_shipping_method');
