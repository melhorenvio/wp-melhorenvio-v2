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

        foreach ($order->get_items() as $key => $itemProduct) {
            $metas = $wooCommerceBundleProductService->getMetas($itemProduct);

            if ($wooCommerceBundleProductService->isBundledItem($metas)) {
                $bundleType = $wooCommerceBundleProductService->getBundledItemType($metas);
                if ($bundleType == WooCommerceBundleProductsService::BUNDLE_TYPE_INTERNAL) {
                    $products = $wooCommerceBundleProductService->getProductsInternal(
                        $itemProduct->get_data(),
                        $metas,
                        $products
                    );
                    continue;
                }

                if ($bundleType == WooCommerceBundleProductsService::BUNDLE_TYPE_EXTERNAL) {
                    $productsInBundle = $wooCommerceBundleProductService->getProducts($metas['_stamp']);
                    foreach ($productsInBundle as $prod) {
                        $productsIgnoreBundle[] = $prod->id;
                    }
                    $product = $wooCommerceBundleProductService->getProductExternal(
                        $itemProduct->get_data(),
                        $metas
                    );
                    $quantities = $this->incrementQuantity($product->id, $quantities, $product->quantity);
                    $products[$product->id] = $product;
                    continue;
                }
            }
            
            $product = $itemProduct->get_product();
            if (is_bool($product) || get_class($product) === CompositeProductBundleService::PRODUCT_COMPOSITE) {
                $compositeBundleService = new CompositeProductBundleService($itemProduct);
                $productComposite = $compositeBundleService->getProductNormalize();

                if (empty($productComposite)) {
                    continue;
                }
                $productsComposite[$key] = $productComposite;
            }

            if (!in_array($product->get_id(), $productsIgnoreBundle)) {
                $productId = $product->get_id();
                $quantity = $itemProduct->get_quantity();

                $products[$productId] = (object) [
                    "id" => $product->get_id(),
                    "name" => $product->get_name(),
                    "quantity" => $itemProduct->get_quantity(),
                    "unitary_value" => round($product->get_price(), 2),
                    "insurance_value" => round($product->get_price(), 2),
                    "weight" => DimensionsHelper::convertWeightUnit($product->get_weight()),
                    "width" => DimensionsHelper::convertUnitDimensionToCentimeter($product->get_width()),
                    "height" => DimensionsHelper::convertUnitDimensionToCentimeter($product->get_height()),
                    "length" => DimensionsHelper::convertUnitDimensionToCentimeter($product->get_length()),
                    "is_virtual" => $product->get_virtual()
                ];

                $quantities = $this->incrementQuantity(
                    $productId,
                    $quantities,
                    $quantity
                );
            }
        }

        if (isset($compositeBundleService)) {
            return $compositeBundleService->selectProductsToReturnByTypeComposite(
                $productsComposite,
                $products
            );
        }

        foreach ($products as $key => $product) {
            if (!empty($quantities[$product->id])) {
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
