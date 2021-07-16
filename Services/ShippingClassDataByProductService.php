<?php

namespace Services;

class ShippingClassDataByProductService
{
    /**
     *
     *
     * @param $productId
     * @return array
     */
    public function get($productId)
    {
        $product = wc_get_product( $productId );
        $product_class_id = $product->get_shipping_class_id();
        $zone_ids = array_keys( array('') + \WC_Shipping_Zones::get_zones() );

        $settings = [
            'additional_tax' => 0,
            'additional_time' => 0,
            'percent_tax' => 0
        ];

        foreach ( $zone_ids as $zone_id ) {
            $shipping_zone = new \WC_Shipping_Zone($zone_id);
            $shipping_methods = $shipping_zone->get_shipping_methods( true, 'values' );
            foreach ( $shipping_methods as $instance_id => $shipping_method ) {
                $dataShippingMethod[$shipping_method->instance_id] = $shipping_method->instance_settings;
                if (isset($dataShippingMethod[$shipping_method->instance_id]['shipping_class_id']) && $product_class_id == $dataShippingMethod[$shipping_method->instance_id] ['shipping_class_id']) {
                    unset($dataShippingMethod[$shipping_method->instance_id]['shipping_class_id']);
                    unset($dataShippingMethod[$shipping_method->instance_id]['title']);
                    $settings = $dataShippingMethod;
                }
            }
        }

        return $settings;
    }

}
