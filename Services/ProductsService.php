<?php

namespace Services;

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
        if (is_object($products)) {
            return $products->price;
        }

        $insuranceValue = 0;
        foreach ($products as $product) {
            if (is_object($product)) {
                $insuranceValue = $insuranceValue + $product->price;
            }

            if (is_array($product)) {

                if (!empty($product['data']) && is_object($product['data'])) {
                    $value = $product['data']->get_price();
                }

                if (!empty($product['unitary_value'])) {
                    $value = $product['unitary_value'] * $product['quantity'];
                }

                $insuranceValue = $insuranceValue + $value;
            }
        }

        return $insuranceValue;
    }

    /**
     * Function to remove the price field from 
     * the product to perform the quote without insurance value
     *
     * @param array|object $products
     * @return array|object
     */
    public function removePrice($products)
    {
        if (is_object($products)) {
            unset($products->price);
            unset($products->insurance_value);
            return $products;
        }

        foreach ($products as $key => $product) {
            if (is_object($product)) {
                unset($products[$key]->price);
                unset($products[$key]->insurance_value);
            } else {
                unset($products[$key]['price']);
                unset($products[$key]['insurance_value']);
            }
        }

        return $products;
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

        foreach ($data as $item) {
            if (empty($item['data'])) {
                $products[] = $item;
            } else {
                $product = $item['data'];
                $products[] = [
                    'id' =>  $product->get_name(),
                    'width' =>  $product->get_width(),
                    'height' =>  $product->get_height(),
                    'length' => $product->get_length(),
                    'weight' =>  $product->get_weight(),
                    'unitary_value' =>  $product->get_price(),
                    'insurance_value' => $product->get_price(),
                    'quantity' =>   $item['quantity']
                ];
            }
        }

        return $products;
    }
}
