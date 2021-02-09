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

        $productsComposite = [];

        foreach ($order->get_items() as $key => $item_product) {

            $_product = $item_product->get_product();

            if (is_bool($_product) || get_class($_product) === CompositeProductBundleService::PRODUCT_COMPOSITE) {

                $compositeBundleService = new CompositeProductBundleService($item_product);

                $productComposite = $compositeBundleService->getProductNormalize();

                if (empty($productComposite)) {
                    continue;
                }

                $productsComposite[$key] = $productComposite;
            }

            $products[$key] = [
                "id" => $_product->get_id(),
                "name" => $_product->get_name(),
                "quantity" => $item_product->get_quantity(),
                "unitary_value" => round($_product->get_price(), 2),
                "insurance_value" => round($_product->get_price(), 2),
                "weight" => DimensionsHelper::convertWeightUnit($_product->get_weight()),
                "width" => DimensionsHelper::convertUnitDimensionToCentimeter($_product->get_width()),
                "height" => DimensionsHelper::convertUnitDimensionToCentimeter($_product->get_height()),
                "length" => DimensionsHelper::convertUnitDimensionToCentimeter($_product->get_length())
            ];
        }

        if (isset($compositeBundleService)) {
            return $compositeBundleService->selectProductsToReturnByTypeComposite(
                $productsComposite, 
                $products
            );
        }

        return $products;
    }
}
