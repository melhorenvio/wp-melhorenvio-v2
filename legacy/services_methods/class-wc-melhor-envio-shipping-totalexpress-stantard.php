<?php

if (class_exists('WC_Melhor_Envio_Shipping')) {
   class WC_Melhor_Envio_Shipping_TotalExpress_Standard extends WC_Melhor_Envio_Shipping   {

       const ID = 'melhorenvio_totalexpress_standard';

       const TITLE = 'Total Express Standard';

       const METHOD_TITLE = "Total Express Standard (Melhor Envio)";

       public $code = 35;

       public $company = 'TotalExpress';

       /**
        * Initialize Total Express Standard.
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