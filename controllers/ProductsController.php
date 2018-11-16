<?php

namespace Controllers;

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

            $product_id = $item_product->get_product_id();
            $_product = $item_product->get_product();
            $products[] = [
                "name" => $_product->get_name(),
                "quantity" => $item_product->get_quantity(),
                "unitary_value" => round($_product->get_price(), 2),
                "insurance_value" => round($_product->get_price(), 2),
                "weight" => $this->converterIfNecessary($_product->weight),
                "width" => $_product->width,
                "height" => $_product->height,
                "length" => $_product->length
            ];
        }

        return $products;
    }

    /**
     * @return void
     */
    public function getProductsCart() 
    {
        global $woocommerce;
        $items = $woocommerce->cart->get_cart();

        $products = [];
        foreach( $items as $item_id => $item_product ){

            $products[] = [
                'id' => $item_product['product_id'],
                'weight' => $this->converterIfNecessary($item_product['data']->get_weight()),
                'width' => $item_product['data']->get_width(),
                'height' => $item_product['data']->get_height(),
                'length' => $item_product['data']->get_length(),
                'quantity' => $item_product['quantity'],
                'insurance_value' => round($item_product['data']->get_price(), 2)
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
}
