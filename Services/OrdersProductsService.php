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

        foreach ($order->get_items() as $key => $item_product) {

            //echo '<pre>';
            //var_dump($orderId);

            $_product = $item_product->get_product();

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

            if (is_bool($_product) || get_class($_product) === self::PRODUCT_COMPOSITE) {

                $shipping_fee = get_post_meta($_product->get_id(), self::PRODUCT_COMPOSITE_SHIPPING_FEE, true);
                $pricing = get_post_meta($_product->get_id(), self::PRODUCT_COMPOSITE_PRICING, true);

                /**var_dump($pricing);
                var_dump(self::PRODUCT_COMPOSITE_PRICING_INCLUDE);
                var_dump($pricing == self::PRODUCT_COMPOSITE_PRICING_INCLUDE);
                var_dump(($pricing == self::PRODUCT_COMPOSITE_PRICING_INCLUDE)
                    ? round($_product->get_price(), 2)
                    : 0
                );

                var_dump($shipping_fee);
                var_dump(($shipping_fee == self::PRODUCT_COMPOSITE_SHIPPING_FEE_WHOLE )
                    ? DimensionsHelper::convertWeightUnit($_product->get_weight())
                    : 0);*/

                $products[$key] = [
                    'unitary_value' => ($pricing == self::PRODUCT_COMPOSITE_PRICING_EXCLUDE)
                        ? round($_product->get_price(), 2)
                        : 0,
                    'insurance_value' => ($pricing == self::PRODUCT_COMPOSITE_PRICING_EXCLUDE)
                        ? round($_product->get_price(), 2)
                        : 0,
                    "weight" => ($shipping_fee == self::PRODUCT_COMPOSITE_SHIPPING_FEE_WHOLE )
                        ? DimensionsHelper::convertWeightUnit($_product->get_weight())
                        : 0,
                    "width" => ($shipping_fee == self::PRODUCT_COMPOSITE_SHIPPING_FEE_WHOLE )
                        ? DimensionsHelper::convertUnitDimensionToCentimeter($_product->get_width())
                        : 0,
                    "height" => ($shipping_fee == self::PRODUCT_COMPOSITE_SHIPPING_FEE_WHOLE )
                        ? DimensionsHelper::convertUnitDimensionToCentimeter($_product->get_height())
                        : 0,
                    "length" => ($shipping_fee == self::PRODUCT_COMPOSITE_SHIPPING_FEE_WHOLE )
                        ? DimensionsHelper::convertUnitDimensionToCentimeter($_product->get_length())
                        : 0
                ];
            }
        }

        //echo '*********************************************************************';

        return $products;
    }
}
