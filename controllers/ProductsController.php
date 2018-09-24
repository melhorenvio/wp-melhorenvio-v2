<?php

namespace Controllers;

class ProductsController {

    public function getProductsOrder($order_id) {

        $order  = wc_get_order( $order_id );
        $products = [];

        foreach( $order->get_items() as $item_id => $item_product ){

            $product_id = $item_product->get_product_id();
            $_product = $item_product->get_product();
            $products[] = [
                "name" => $_product->get_name(),
                "quantity" => $item_product->get_quantity(),
                "unitary_value" => round($_product->get_price(), 2),
                "weight" => $_product->weight
            ];
            
        }
        return $products;
    }

    public function getInsuranceValue($order_id) {

        $order  = wc_get_order( $order_id );
        $total = 0;

        foreach( $order->get_items() as $item_id => $item_product ){
            $_product = $item_product->get_product();
            $total = $total + ($_product->get_price() + $item_product->get_quantity());
        }

        return round($total, 2);
    }
}
