<?php

class WC_Melhor_Envio_Shipping_Correios_Mini extends WC_Melhor_Envio_Shipping
{

    const ID = 'melhorenvio_correios_mini';

    const TITLE = 'Correios Mini';

    const METHOD_TITLE = "Correios Mini (Melhor Envio)";

    protected $code = 17;

    /**
     * Initialize Correios Mini.
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
