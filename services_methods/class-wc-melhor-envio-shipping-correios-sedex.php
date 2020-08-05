<?php

use Services\CalculateShippingMethodService;

class WC_Melhor_Envio_Shipping_Correios_Sedex extends WC_Melhor_Envio_Shipping
{

    const ID = 'melhorenvio_correios_sedex';

    const TITLE = 'Correios Sedex';

    const METHOD_TITLE = "Correios Sedex (Melhor Envio)";

    protected $code = 2;

    /**
     * Initialize Correios Sedex.
     *
     * @param int $instance_id Shipping zone instance.
     */
    public function __construct($instance_id = 0)
    {
        $this->id = self::ID;
        $this->method_title = self::METHOD_TITLE;
        $this->title = self::TITLE;
        parent::__construct($instance_id);
    }
}
