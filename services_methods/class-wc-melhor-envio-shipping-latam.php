<?php

class WC_Melhor_Envio_Shipping_Latam extends WC_Melhor_Envio_Shipping
{

    const ID = 'melhorenvio_latam';

    const TITLE = 'Latam';

    const METHOD_TITLE = "Latam (Melhor Envio)";

    protected $code = 10;

    /**
     * Initialize Latam.
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
