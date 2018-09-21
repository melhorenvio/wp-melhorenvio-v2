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
        
        return [
            "weight" => $weight,
            "width" => $width,
            "height" => $height,
            "length" => $length
        ];

    }

}

