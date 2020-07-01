<?php

namespace Models;

use Controller\HelperController;

class Cart 
{
    /**
     * @return Array
     */
    public function getProductsOnCart() 
    {
        global $woocommerce;

        $items = $woocommerce->cart->get_cart();

        $products = array();

        foreach( $items as $item_id => $item_product ){

            $productId = ($item_product['variation_id'] != 0) ? $item_product['variation_id'] : $item_product['product_id'];

            $productInfo = wc_get_product( $productId );

            if(!$productInfo || empty($productInfo)) {
                continue;
            } else {
                $data = $productInfo->get_data();

                $products[] = (object) array(
                    'id'           => $item_product['product_id'],
                    'variation_id' => $item_product['variation_id'],
                    'name'         => $data['name'],
                    'price'        => $productInfo->get_price(),
                    'height'       => $productInfo->get_height(),
                    'width'        => $productInfo->get_width(),
                    'length'       => $productInfo->get_length(),
                    'weight'       => $productInfo->get_weight(),
                    'quantity'     => (isset($item_product['quantity'])) ? intval($item_product['quantity']) : 1,
                );
            }

            
        }

        return (object) $products;
    }
}
