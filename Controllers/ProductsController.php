<?php

namespace Controllers;


class ProductsController 
{
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
}
