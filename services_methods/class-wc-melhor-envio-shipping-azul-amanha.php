<?php

use Services\CalculateShippingMethodService;

class WC_Melhor_Envio_Shipping_Azul_Amanha extends WC_Melhor_Envio_Shipping
{

    const ID = 'melhorenvio_azul_amanha';

    const TITLE = 'Azul Amanhã';

    const METHOD_TITLE = "Azul Amanhã (Melhor Envio)";

    protected $code = 15;

    /**
     * Initialize Azul Amanhã.
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
