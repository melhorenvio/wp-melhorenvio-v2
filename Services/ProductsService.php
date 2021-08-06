<?php

namespace Services;

use Helpers\DimensionsHelper;
use Services\WooCommerceBundleProductsService;

class ProductsService
{
    /**
     * @param int $postId
     * @param null|int $quantity
     * @return object
     */
    public function getProduct(int $postId, int $quantity = null)
    {
        $product = wc_get_product($postId);

        if (empty($quantity)) {
            $quantity = 1;
        }

        return (object) [
            'id' => $postId,
            'name' => $product->get_name(),
            'quantity' => $quantity,
            'unitary_value' => $product->get_price(),
            'insurance_value' => ($product->get_price() * $quantity),
            'width' =>  DimensionsHelper::convertUnitDimensionToCentimeter($product->get_width()),
            'height' =>  DimensionsHelper::convertUnitDimensionToCentimeter($product->get_height()),
            'length' => DimensionsHelper::convertUnitDimensionToCentimeter($product->get_length()),
            'weight' =>  DimensionsHelper::convertWeightUnit($product->get_weight()),
        ];
    }

    /**
     * Function to obtain the insurance value of one or more products.
     *
     * @param array|object $products
     * @return float
     */
    public function getInsuranceValue($products)
    {
        $insuranceValue = 0;
        foreach ($products as $product) {
            $product = (object) $product;
            if (!empty($product->unitary_value)) {
                $insuranceValue += $product->unitary_value * $product->quantity;
            }
        }

        if ($insuranceValue == 0) {
            $insuranceValue = floatval(1);
        }

        return $insuranceValue;
    }

    /**
     * function to remove the price field from
     * the product to perform the quote without insurance value
     *
     * @param array $products
     * @return array
     */
    public function removePrice($products)
    {
        $response = [];
        foreach ($products as $product) {
            $response[] = (object) [
                'id' => $product->id,
                'name' => $product->name,
                'quantity' => $product->quantity,
                'unitary_value' => $product->unitary_value,
                'weight' => $product->weight,
                'width' => $product->width,
                'height' => $product->height,
                'length' => $product->length,
            ];
        }

        return $response;
    }

    /**
     * Function to filter products to api Melhor Envio.
     *
     * @param array $products
     * @return array
     */
    public function filter($data)
    {
        $products = [];

        $noticeService = new SessionNoticeService();
        foreach ($data as $item) {
            if (!is_array($item) && (get_class($item) == 'WC_Product_Simple' || get_class($item) == 'WC_Product_Bundle')) {
                $data = $item->get_data();
                $product = $item;
                $products[] = (object) [
                    'id' =>  $product->get_id(),
                    'name' =>  $product->get_name(),
                    'width' =>  DimensionsHelper::convertUnitDimensionToCentimeter($product->get_width()),
                    'height' =>  DimensionsHelper::convertUnitDimensionToCentimeter($product->get_height()),
                    'length' => DimensionsHelper::convertUnitDimensionToCentimeter($product->get_length()),
                    'weight' =>  DimensionsHelper::convertWeightUnit($product->get_weight()),
                    'unitary_value' => (!empty($data['price'])) ? floatval($data['price']) : 0,
                    'insurance_value' => (!empty($data['price'])) ? floatval($data['price']) : 0,
                    'quantity' => 1 //todo: fazer isso.
                ];
                continue;
            }

            if (!empty($item->name) && !empty($item->id)) {
               $products[] = $item;
               continue;
            }

            $product = $item['data'];

            $products[] = (object) [
                'id' =>  $product->get_id(),
                'name' =>  $product->get_name(),
                'width' =>  DimensionsHelper::convertUnitDimensionToCentimeter($product->get_width()),
                'height' =>  DimensionsHelper::convertUnitDimensionToCentimeter($product->get_height()),
                'length' => DimensionsHelper::convertUnitDimensionToCentimeter($product->get_length()),
                'weight' =>  DimensionsHelper::convertWeightUnit($product->get_weight()),
                'unitary_value' => floatval($product->get_price()),
                'insurance_value' => floatval($product->get_price()),
                'quantity' => intval($item['quantity'])
            ];
        }

        return $products;
    }

    private function normalize($product, $quantity = 1)
    {
        return (object) [
            'id' =>  $product->get_id(),
            'name' =>  $product->get_name(),
            'width' =>  DimensionsHelper::convertUnitDimensionToCentimeter($product->get_width()),
            'height' =>  DimensionsHelper::convertUnitDimensionToCentimeter($product->get_height()),
            'length' => DimensionsHelper::convertUnitDimensionToCentimeter($product->get_length()),
            'weight' =>  DimensionsHelper::convertWeightUnit($product->get_weight()),
            'unitary_value' => (!empty($data['price'])) ? floatval($data['price']) : 0,
            'insurance_value' => (!empty($data['price'])) ? floatval($data['price']) : 0,
            'quantity' =>   $quantity,
            'class' => get_class($item)
        ];
    }

    /**
     * function to check if prouct has all dimensions.
     *
     * @param object $product
     * @return boolean
     */
    private function hasAllDimensions($product)
    {
        return (!empty($product->get_width()) &&
            !empty($product->get_height()) &&
            !empty($product->get_length()) &&
            !empty($product->get_weight()));
    }

    /**
     * function to return a label with the name of products.
     *
     * @param array $products
     * @return string
     */
    public function createLabelTitleProducts($products)
    {
        $title = '';
        foreach ($products as $id => $product) {
            if (!empty($product['data']->get_name())) {
                $title = $title . sprintf(
                    "<a href='%s'>%s</a>, ",
                    get_edit_post_link($id),
                    $product['data']->get_name()
                );
            }
        }

        if (!empty($title)) {
            $title = substr($title, 0, -2);
        }

        return 'Produto(s): ' . $title;
    }
}
