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
        $pricing = false;
        $shipping_fee = false;

        foreach ($order->get_items() as $key => $item_product) {

            $_product = $item_product->get_product();

            /**
             * Aqui é verificado se o produto é da classe do plugin Composite Product,
             * ou seja, esse produto possui regras para essa classe.
             * O produto pode ter as medidas da embalagem principal ou dos produtos interno da embalagem principal.
             * SHIPPING_FEE (post_meta shipping_fee)
             * - WHOLE = usa as medidas da caixa principal
             * - EACH = usa as medidas de cada produto de contém a embalagem principal.
             *
             * O produto pode ter o preço da embalagem principal,
             * da soma de dos produtos + embalagem ou apenas o preço dos prodtuos internos.
             * PRICING(post_meta pricing)
             * - ONLY = apenas o preço da embalagem princiapl
             * - INCLUDE = o preço da embalagem principal + preço dos produtos internos
             * - EXCLUDE = apenas o preços dos produtos internos.
             */
            if (is_bool($_product) || get_class($_product) === CompositeProductBundleService::PRODUCT_COMPOSITE) {

                $shipping_fee = CompositeProductBundleService::getShippingFeeType($_product->get_id());
                $pricing = CompositeProductBundleService::getPricingType($_product->get_id());

                if ($shipping_fee == CompositeProductBundleService::PRODUCT_COMPOSITE_SHIPPING_FEE_WHOLE) {
                    $productsComposite[$key] = [
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

                if ($shipping_fee == CompositeProductBundleService::PRODUCT_COMPOSITE_SHIPPING_FEE_EACH) {
                    continue;
                }
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

        if (CompositeProductBundleService::isCompositeWholeAndOnly($productsComposite, $shipping_fee, $pricing)) {
            return $productsComposite;
        }

        if (CompositeProductBundleService::isCompositeWholeAndInclude($productsComposite, $shipping_fee, $pricing)) {
            $value = 0;
            foreach ($products as $product) {
                $value += $product['insurance_value'];
            }
            foreach($productsComposite as $key => $product) {
                $productsComposite[$key]['unitary_value'] = $value;
                $productsComposite[$key]['insurance_value'] = $value;

            }
            return $productsComposite;
        }

        if (CompositeProductBundleService::isCompositeWholeAndExclude($productsComposite, $shipping_fee, $pricing)) {
            foreach($productsComposite as $key => $product) {
                $productsComposite[$key]['unitary_value'] = $_product->get_price();
                $productsComposite[$key]['insurance_value'] = $_product->get_price();
            }
            return $productsComposite;
        }

        return $products;
    }
}
