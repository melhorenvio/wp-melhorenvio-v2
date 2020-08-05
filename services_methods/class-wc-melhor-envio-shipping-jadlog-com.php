<?php

use Services\CalculateShippingMethodService;

class WC_Melhor_Envio_Shipping_Jadlog_Com extends WC_Melhor_Envio_Shipping
{

    const ID = 'melhorenvio_jadlog_com';

    const TITLE = 'Jadlog .Com';

    const METHOD_TITLE = "Jadlog .Com (Melhor Envio)";

    protected $code = 4;

    /**
     * Initialize Jadlog .COm.
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
