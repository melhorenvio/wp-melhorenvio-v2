<?php

namespace Controllers;

class ProductsController
{
    /**
     * @param int $orderId
     * @return float
     */
    public function getInsuranceValue($orderId)
    {
        $order  = wc_get_order($orderId);
        $total = 0;

        foreach ($order->get_items() as $itemProduct) {
            $product = $itemProduct->get_product();
            $total = $total + ($product->get_price() * $itemProduct->get_quantity());
        }

        return round($total, 2);
    }
}
