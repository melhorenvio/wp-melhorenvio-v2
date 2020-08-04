<?php

class WC_Melhor_Envio_Shipping_Correios_Pac extends WC_Melhor_Envio_Shipping
{

    const ID = 'melhorenvio_correios_pac';

    const TITLE = 'Correios Pac';

    const METHOD_TITLE = "Correios Pac (Melhor Envio)";

    protected $code = 1;

    /**
     * Initialize Correios Pac.
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
