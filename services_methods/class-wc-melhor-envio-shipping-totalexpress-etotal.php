<?php

if (class_exists('WC_Melhor_Envio_Shipping')) {
   class WC_Melhor_Envio_Shipping_TotalExpress_ETotal extends WC_Melhor_Envio_Shipping   {

       const ID = 'melhorenvio_totalexpress_etotal';

       const TITLE = 'Total Express e-Total';

       const METHOD_TITLE = "Total Express e-Total (Melhor Envio)";

       public $code = 18;

       public $company = 'TotalExpress';

       /**
        * Initialize Total Express e-Total.
        *
        *@param int $instance_id Shipping zone instance.
        */

       public function __construct($instance_id = 0){
           $this->id = self::ID;
           $this->method_title = self::METHOD_TITLE;
           parent::__construct($instance_id);
       }

   }

}