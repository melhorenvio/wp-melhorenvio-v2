<?php

namespace Controllers;

class PackageController {

    public function getPackage($package) {

        $weight = 0;
        $width = 0;
        $height = 0;
        $length = 0;

        foreach ($package['contents'] as $item_id => $values) {
            
            $_product = $values['data'];
            $weight = $weight + $_product->get_weight() * $values['quantity'];

            $width  += $_product->width;
            $height += $_product->height;
            $length += $_product->length;
        }
        
        return $this->converterIfNecessary([
            "weight" => $weight,
            "width" => $width,
            "height" => $height,
            "length" => $length
        ]);
    }

    public function getPackageOrder($order_id) {

        $weight = 0;
        $width  = 0;
        $height = 0;
        $length = 0;
        $order  = wc_get_order( $order_id );

        foreach( $order->get_items() as $item_id => $item_product ){

            $product_id = $item_product->get_product_id();
            $_product = $item_product->get_product();

            $weight = $weight + $_product->weight * $item_product->get_quantity();
            $width  += $_product->width;
            $height += $_product->height;
            $length += $_product->length;
        }

        return $this->converterIfNecessary([
            "weight" => $weight,
            "width"  => $width,
            "height" => $height,
            "length" => $length
        ]);
    }

    private function converterIfNecessary($package) {
        $weight_unit = get_option('woocommerce_weight_unit');
        if ($weight_unit == 'g') {
            $package['weight'] = $package['weight'] / 1000;
        }
        return $package;
    }

}

// TODO LIST
// - Verificar se existem todas medidas