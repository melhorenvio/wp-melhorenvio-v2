<?php

namespace Models;

use Helpers\DimensionsHelper;

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

        $dimenstionHelpder = new DimensionsHelper();

        foreach( $items as $item_product ){

            $productId = ($item_product['variation_id'] != 0) ? $item_product['variation_id'] : $item_product['product_id'];

            $productInfo = wc_get_product( $productId );

            if (empty($productInfo)) {
                continue;
            } else {
                $data = $productInfo->get_data();

                $products[] = (object) array(
                    'id'           => $item_product['product_id'],
                    'variation_id' => $item_product['variation_id'],
                    'name'         => $data['name'],
                    'price'        => $productInfo->get_price(),
                    'height'       => $dimensionHelper->ConverterUnitDimensionToCentimeter($productInfo->get_height()),
                    'width'        => $dimensionHelper->ConverterUnitDimensionToCentimeter($productInfo->get_width()),
                    'length'       => $dimensionHelper->ConverterUnitDimensionToCentimeter($productInfo->get_length()),
                    'weight'       => $dimensionHelper->convertWeightUnit($productInfo->get_weight()),
                    'quantity'     => (isset($item_product['quantity'])) ? intval($item_product['quantity']) : 1,
                );
            }

            
        }

        return (object) $products;
    }
}
