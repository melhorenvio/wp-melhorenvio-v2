<?php

namespace Controllers;

use Controllers\HelperController;

class ProductsController 
{
    /**
     * @param [type] $order_id
     * @return void
     */
    public function getProductsOrder($order_id) 
    {
        $order  = wc_get_order( $order_id );
        $products = [];

        foreach( $order->get_items() as $item_id => $item_product ){

            $_product = $item_product->get_product();
<<<<<<< HEAD

            if (is_bool($_product)) {
                continue;
            }

            $products[] = [
                "name" => $_product->get_name(),
                "quantity" => $item_product->get_quantity(),
                "unitary_value" => round($_product->get_price(), 2),
                "insurance_value" => round($_product->get_price(), 2),
                "weight" => $this->converterIfNecessary($_product->weight),
                "width" => $this->converterDimension($_product->width),
                "height" => $this->converterDimension($_product->height),
                "length" => $this->converterDimension($_product->length)
            ];
        }

        return $products;
    }
=======
>>>>>>> master

            if (is_bool($_product)) {
                continue;
            }

            $products[] = [
<<<<<<< HEAD
                'id' => $item_product['product_id'],
                'weight' => $this->converterIfNecessary($item_product['data']->get_weight()),
                'width'  => $this->converterDimension($item_product['data']->get_width()),
                'height' => $this->converterDimension($item_product['data']->get_height()),
                'length' => $this->converterDimension($item_product['data']->get_length()),
                'quantity' => $item_product['quantity'],
                'insurance_value' => round($item_product['data']->get_price(), 2)
=======
                "name"            => $_product->get_name(),
                "quantity"        => $item_product->get_quantity(),
                "unitary_value"   => round($_product->get_price(), 2),
                "insurance_value" => round($_product->get_price(), 2),
                "weight"          => (new HelperController())->converterIfNecessary($_product->weight),
                "width"           => (new HelperController())->converterDimension($_product->width),
                "height"          => (new HelperController())->converterDimension($_product->height),
                "length"          => (new HelperController())->converterDimension($_product->length)
>>>>>>> master
            ];
        }

        return $products;
    }

    /**
     * @param [type] $order_id
     * @return void
     */
    public function getInsuranceValue($order_id) 
    {
        $order  = wc_get_order( $order_id );
        $total = 0;

        foreach( $order->get_items() as $item_id => $item_product ){
            $_product = $item_product->get_product();
            $total = $total + ($_product->get_price() * $item_product->get_quantity());
        }   

        return round($total, 2);
    }
<<<<<<< HEAD

    /**
     * @param [type] $weight
     * @return void
     */
    private function converterIfNecessary($weight) 
    {
        $weight_unit = get_option('woocommerce_weight_unit');
        if ($weight_unit == 'g') {
            $weight = $weight / 1000;
        }
        return $weight;
    }

    private function converterDimension($value)
    {
        $unit = get_option('woocommerce_dimension_unit');
        if ($unit == 'mm') {
            return $value / 10;
        }

        if ($unit == 'm') {
            return $value * 10;
        }

        return $value;
    }
=======
>>>>>>> master
}
