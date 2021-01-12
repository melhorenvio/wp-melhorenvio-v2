<?php

namespace Services;


class CompositeProductBundleService
{
    const PRODUCT_COMPOSITE = 'WC_Product_Composite';

    const PRODUCT_COMPOSITE_SHIPPING_FEE = 'wooco_shipping_fee';

    const PRODUCT_COMPOSITE_PRICING = 'wooco_pricing';

    const PRODUCT_COMPOSITE_SHIPPING_FEE_EACH = 'each';

    const PRODUCT_COMPOSITE_SHIPPING_FEE_WHOLE = 'whole';

    const PRODUCT_COMPOSITE_PRICING_INCLUDE = 'include';

    const PRODUCT_COMPOSITE_PRICING_EXCLUDE = 'exclude';

    const PRODUCT_COMPOSITE_PRICING_ONLY = 'only';

    /**
     * Function to check product is shippging == whole and pricing == 'only
     *
     * @param $productsComposite
     * @param $shipping_fee
     * @param $pricing
     * @return bool
     */
    public static function isCompositeWholeAndOnly($productsComposite, $shipping_fee, $pricing)
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
    public static function isCompositeWholeAndInclude($productsComposite, $shipping_fee, $pricing)
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
    public static function isCompositeWholeAndExclude($productsComposite, $shipping_fee, $pricing)
    {
        return (
            !empty($productsComposite) &&
            $shipping_fee == self::PRODUCT_COMPOSITE_SHIPPING_FEE_WHOLE &&
            $pricing == self::PRODUCT_COMPOSITE_PRICING_EXCLUDE
        );
    }

    /**
     * Function to get type pricing
     *
     * @param int $product_id
     * @return string
     */
    public static function getPricingType($product_id)
    {
        return get_post_meta($product_id, self::PRODUCT_COMPOSITE_PRICING, true);
    }

   /**
    * Function to get type shipping fee
    *
    * @param int $product_id
    * @return string
    */
    public static function getShippingFeeType($product_id)
    {
        return get_post_meta($product_id, self::PRODUCT_COMPOSITE_SHIPPING_FEE, true);
    }
}