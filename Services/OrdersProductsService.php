<?php

namespace Services;

use Helpers\DimensionsHelper;

class OrdersProductsService
{
    /**
     * Get products by order
     *
     * @param int $orderId
     * @return array $products
     */
    public function getProductsOrder($orderId)
    {
        $order  = wc_get_order($orderId);
        $products = [];

        foreach ($order->get_items() as $item_product) {
            $_product = $item_product->get_product();

            if (is_bool($_product)) {
                continue;
            }

            $products[] = [
                "id"              => $_product->get_id(),
                "name"            => $_product->get_name(),
                "quantity"        => $item_product->get_quantity(),
                "unitary_value"   => round($_product->get_price(), 2),
                "insurance_value" => round($_product->get_price(), 2),
                "weight"          => DimensionsHelper::convertWeightUnit($_product->weight),
                "width"           => DimensionsHelper::convertUnitDimensionToCentimeter($_product->width),
                "height"          => DimensionsHelper::convertUnitDimensionToCentimeter($_product->height),
                "length"          => DimensionsHelper::convertUnitDimensionToCentimeter($_product->length)
            ];
        }

        return $products;
    }
}
