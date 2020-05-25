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
        $shippings = WC()->shipping->get_shipping_methods();

        if (is_null($shippings)) { return []; }

        $codes = [];
        
        foreach ($shippings as $method) {
            if (is_null($method->code)) {
                continue;
            }
            $codes[] = $method->code;
        }

        return $codes;
    }
}