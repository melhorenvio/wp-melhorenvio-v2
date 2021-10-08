<?php

namespace Services;

class ShippingClassDataByProductService
{
    /**
     * @param $productId
     * @return array
     */
    public function get($productId)
    {
        $product = wc_get_product($productId);

        $productClassId = $product->get_shipping_class_id();

        $zones = \WC_Shipping_Zones::get_zones();
        
        $settings = [];

        $settingsDefault = [
            'instance_id' => null,
            'additional_tax' => 0,
            'additional_time' => 0,
            'percent_tax' => 0
        ];

        foreach ($zones as $zone) {
            $shipping_zone = new \WC_Shipping_Zone($zone['id']);
            $methods = $shipping_zone->get_shipping_methods(true, 'values');
            foreach ($methods as $method) {
                if ($productClassId == $method->instance_settings['shipping_class_id']) {
                    $settings = [
                        'instance_id' => $method->instance_settings['shipping_class_id'],
                        'additional_tax' => floatval($method->instance_settings['additional_tax']),
                        'additional_time' => floatval($method->instance_settings['additional_time']),
                        'percent_tax' => floatval($method->instance_settings['percent_tax'])
                    ];
                    break;
                }
            }
        }

        return array_merge($settingsDefault, $settings);
    }
}
