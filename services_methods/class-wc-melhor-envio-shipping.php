<?php

use Services\CalculateShippingMethodService;

/**
 * Default Melhor Envio shipping method abstract class.
 *
 * This is a abstract method with default options for all methods.
 */
abstract class WC_Melhor_Envio_Shipping extends WC_Shipping_Method
{

    /**
     * Service code.
     *
     * @var string
     */
    protected $code = '';

    /**
     * Company name.
     *
     * @var string
     */
    protected $company = '';


    /**
     * Initialize the Melhor Envio shipping method.
     *
     * @param int $instance_id Shipping zone instance ID.
     */
    public function __construct($instance_id = 0)
    {
        $this->instance_id = absint($instance_id);
        $this->method_description = sprintf("Metódo de envio %s do Melhor Envio", $this->method_title);
        $this->supports = array(
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal',
        );
        $this->service = new CalculateShippingMethodService();
        $this->init_form_fields();
        add_action(
            'woocommerce_update_options_shipping_' . $this->id,
            array($this, 'process_admin_options')
        );
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
            $this->code,
            $this->instance_id,
            $this->company
        );

        if ($rate) {
            $this->add_rate($rate);
        }
    }
}
