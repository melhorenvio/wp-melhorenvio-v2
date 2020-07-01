<?php 

namespace Services;

use Helpers\DimensionsHelper;

class OrdersProductsService
{
    /**
     * Get products by order
     *
     * @param int $order_id
     * @return array $products
     */
    public function getProductsOrder($order_id)
    {
        $order  = wc_get_order( $order_id );
        $products = [];

        foreach( $order->get_items() as  $item_product ){

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
                "weight"          => (new DimensionsHelper())->converterIfNecessary($_product->weight),
                "width"           => (new DimensionsHelper())->converterDimension($_product->width),
                "height"          => (new DimensionsHelper())->converterDimension($_product->height),
                "length"          => (new DimensionsHelper())->converterDimension($_product->length)
            ];
        }

        return $products;
    }
}
