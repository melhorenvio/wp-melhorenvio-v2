<?php

use Services\CalculateShippingMethodService;

class WC_Melhor_Envio_Shipping_Via_Brasil_Aero extends WC_Melhor_Envio_Shipping
{

    const ID = 'melhorenvio_via_brasil_aero';

    const TITLE = 'Via Brasil Aero';

    const METHOD_TITLE = "Via Brasil Aero (Melhor Envio)";

    protected $code = 8;

    /**
     * Initialize Via Brasil Aero.
     *
     * @param int $instance_id Shipping zone instance.
     */
    public function __construct($instance_id = 0)
    {
        $this->id = self::ID;
        $this->method_title = self::METHOD_TITLE;
        $this->title = self::TITLE;
        $this->shipping_class_id  = (int) $this->get_option(
            'shipping_class_id',
            CalculateShippingMethodService::ANY_DELIVERY
        );
        parent::__construct($instance_id);
    }
}
