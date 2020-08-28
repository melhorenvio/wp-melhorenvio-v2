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
            return $products->price * $products->quantity;
        }

        $insuranceValue = 0;
        foreach ($products as $product) {
            if (is_object($product)) {
                $insuranceValue = $insuranceValue + ($product->price * $product->quantity);
            }

            if (is_array($product)) {
                $insuranceValue = $insuranceValue + ($product['unitary_value'] * $product['quantity']);
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

}
