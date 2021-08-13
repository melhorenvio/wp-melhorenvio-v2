<?php

namespace Services;

use Helpers\DimensionsHelper;
use Services\WooCommerceBundleProductsService;
use Services\ProductsService;

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

        $productService = new ProductsService();

        $products = [];

        $quantities = [];

        $productsIgnoreBundle = [];

        $wooCommerceBundleProductService = new WooCommerceBundleProductsService();

        foreach ($order->get_items() as $key => $item_product) {    
            
            $metas = $wooCommerceBundleProductService->getMetas($item_product);
            if ($wooCommerceBundleProductService->isBundledItem($metas)) {
                $internalOrExternal = $wooCommerceBundleProductService->getBundledItemType($metas);

                if ($internalOrExternal == WooCommerceBundleProductsService::BUNDLE_TYPE_INTERNAL) {
                    $products = $wooCommerceBundleProductService->getProductsInternal(
                        $item_product->get_data(), 
                        $metas,
                        $products
                    );
                }

                if ($internalOrExternal == WooCommerceBundleProductsService::BUNDLE_TYPE_EXTERNAL) {
                    $productsInBundle = $wooCommerceBundleProductService->getProducts($metas['_stamp']);
                    foreach ($productsInBundle as $prod) {
                        $productsIgnoreBundle[] = $prod->id;
                    }
                    $product = $wooCommerceBundleProductService->getProductExternal(
                        $item_product->get_data(), 
                        $metas
                    );
                    $quantities = $this->incrementQuantity($product->id, $quantities, $product->quantity);
                    $products[$product->id] = $product;
                    continue;
                }
                
            }
            
            $_product = $item_product->get_product();            
            if (is_bool($_product) || get_class($_product) === CompositeProductBundleService::PRODUCT_COMPOSITE) {

                $compositeBundleService = new CompositeProductBundleService($item_product);

                $productComposite = $compositeBundleService->getProductNormalize();

                if (empty($productComposite)) {
                    continue;
                }

                $productsComposite[$key] = $productComposite;
            }

            if (!in_array($_product->get_id(), $productsIgnoreBundle)) {
                $products[$_product->get_id()] = (object) [
                    "id" => $_product->get_id(),
                    "name" => $_product->get_name(),
                    "quantity" => $item_product->get_quantity(),
                    "unitary_value" => round($_product->get_price(), 2),
                    "insurance_value" => round($_product->get_price(), 2),
                    "weight" => DimensionsHelper::convertWeightUnit($_product->get_weight()),
                    "width" => DimensionsHelper::convertUnitDimensionToCentimeter($_product->get_width()),
                    "height" => DimensionsHelper::convertUnitDimensionToCentimeter($_product->get_height()),
                    "length" => DimensionsHelper::convertUnitDimensionToCentimeter($_product->get_length()),
                    "is_virtual" => $_product->get_virtual()
                ];
                $quantities = $this->incrementQuantity($_product->get_id(), $quantities, $item_product->get_quantity());
            }
        }

        if (isset($compositeBundleService)) {
            return $compositeBundleService->selectProductsToReturnByTypeComposite(
                $productsComposite, 
                $products
            );
        }

       foreach ($products as $key => $product) {
            if(!empty($quantities[$product->id])) {
                $products[$key]->quantity = $quantities[$product->id];
            }
       }

        return $products;
    }

    /**
     * @param int $productId
     * @param array $quantities
     * @param int $quantity
     * @return array
     */
    public function incrementQuantity($productId, $quantities, $quantity)
    {
        $actualQuantity = $quantities[$productId];
        if (empty($actualQuantity)) {
            $actualQuantity = 0;
        }

        $quantities[$productId] = $actualQuantity + $quantity;
       return $quantities;
    }
}
