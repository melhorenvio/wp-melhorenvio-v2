<?php

namespace Services;

use Helpers\DimensionsHelper;

class OrdersProductsService
{
    const PRODUCT_COMPOSITE = 'WC_Product_Composite';

    const PRODUCT_COMPOSITE_SHIPPING_FEE = 'wooco_shipping_fee';

    const PRODUCT_COMPOSITE_SHIPPING_FEE_EACH = 'each';

    const PRODUCT_COMPOSITE_SHIPPING_FEE_WHOLE = 'whole';

    const PRODUCT_COMPOSITE_PRICING = 'wooco_pricing';

    const PRODUCT_COMPOSITE_PRICING_INCLUDE = 'include';

    const PRODUCT_COMPOSITE_PRICING_EXCLUDE = 'exclude';

    const PRODUCT_COMPOSITE_PRICING_ONLY = 'only';

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

            if (is_bool($_product) || get_class($_product) === self::PRODUCT_COMPOSITE) {

                $shipping_fee = get_post_meta($_product->get_id(), self::PRODUCT_COMPOSITE_SHIPPING_FEE, true);
                $pricing = get_post_meta($_product->get_id(), self::PRODUCT_COMPOSITE_PRICING, true);

                if ($shipping_fee == self::PRODUCT_COMPOSITE_SHIPPING_FEE_WHOLE) {
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

                if ($shipping_fee == self::PRODUCT_COMPOSITE_SHIPPING_FEE_EACH) {
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

        if ($this->isCompositeWholeAndOnly($productsComposite, $shipping_fee, $pricing)) {
            return $productsComposite;
        }

        if ($this->isCompositeWholeAndInclude($productsComposite, $shipping_fee, $pricing)) {
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

        if ($this->isCompositeWholeAndExclude($productsComposite, $shipping_fee, $pricing)) {
            foreach($productsComposite as $key => $product) {
                $productsComposite[$key]['unitary_value'] = $_product->get_price();
                $productsComposite[$key]['insurance_value'] = $_product->get_price();
            }
            return $productsComposite;
        }

        return $products;
    }

    /**
     * Function to check product is shippging == whole and pricing == 'only
     *
     * @param $productsComposite
     * @param $shipping_fee
     * @param $pricing
     * @return bool
     */
    private function isCompositeWholeAndOnly($productsComposite, $shipping_fee, $pricing)
    {
        return (
            !empty($productsComposite) &&
            $shipping_fee == self::PRODUCT_COMPOSITE_SHIPPING_FEE_WHOLE &&
            $pricing == self::PRODUCT_COMPOSITE_PRICING_ONLY
        );
    }

    /**
     * Function to check product is shippging == whole and pricing == 'include'
     *
     * @param $productsComposite
     * @param $shipping_fee
     * @param $pricing
     * @return bool
     */
    private function isCompositeWholeAndInclude($productsComposite, $shipping_fee, $pricing)
    {
        return (
            !empty($productsComposite) &&
            $shipping_fee == self::PRODUCT_COMPOSITE_SHIPPING_FEE_WHOLE &&
            $pricing == self::PRODUCT_COMPOSITE_PRICING_INCLUDE
        );
    }

    /**
     * Function to check product is shippging == whole and pricing == 'exclude'
     *
     * @param $productsComposite
     * @param $shipping_fee
     * @param $pricing
     * @return bool
     */
    private function isCompositeWholeAndExclude($productsComposite, $shipping_fee, $pricing)
    {
        return (
            !empty($productsComposite) &&
            $shipping_fee == self::PRODUCT_COMPOSITE_SHIPPING_FEE_WHOLE &&
            $pricing == self::PRODUCT_COMPOSITE_PRICING_EXCLUDE
        );
    }
}
