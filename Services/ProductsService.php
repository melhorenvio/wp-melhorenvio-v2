<?php

namespace Services;

use Helpers\DimensionsHelper;
use Services\SessionNoticeService;

class ProductsService
{
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
            if (empty($item['data'])) {
                $products[] = (object) $item;
            } else {
                $product = $item['data'];

                if (!$this->hasAllDimensions(($product))) {
                    $message = sprintf(
                        "Verificar as medidas do produto  <a href='%s'>%s</a>",
                        get_edit_post_link($product->get_id()),
                        $product->get_name()
                    );
                    $noticeService->add($message, SessionNoticeService::NOTICE_INFO);
                }

                $products[] = (object) [
                    'id' =>  $product->get_id(),
                    'name' =>  $product->get_name(),
                    'width' =>  DimensionsHelper::convertUnitDimensionToCentimeter($product->get_width()),
                    'height' =>  DimensionsHelper::convertUnitDimensionToCentimeter($product->get_height()),
                    'length' => DimensionsHelper::convertUnitDimensionToCentimeter($product->get_length()),
                    'weight' =>  DimensionsHelper::convertWeightUnit($product->get_weight()),
                    'unitary_value' => $product->get_price(),
                    'insurance_value' => $product->get_price(),
                    'quantity' =>   $item['quantity']
                ];
            }
        }

        return $products;
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
