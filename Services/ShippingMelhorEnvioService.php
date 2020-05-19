<?php

namespace Services;

class ShippingMelhorEnvioService
{
    public function getCodesEnableds()
    {
        return $this->getCodesWcShippingClass();
    }

    public function getStringCodesEnables()
    {
        return implode(",",$this->getCodesEnableds());
    }

    public function getCodesWcShippingClass()
    {
        $Shipping = new \WC_Shipping();

        if (is_null($shipping)) { return []; }
        
        $shippingMethods = $Shipping->get_shipping_methods();
        $codes = [];
        
        foreach ($shippingMethods as $method) {
            if (is_null($method->code)) {
                continue;
            }
            $codes[] = $method->code;
        }

        return $codes;
    }
}