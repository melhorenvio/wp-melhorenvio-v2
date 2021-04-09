<?php

namespace Services;

/**
 * Class ProcessAdditionalTaxService
 * @package Services
 */
class ProcessAdditionalTaxService
{
    public function init()
    {
        add_action('woocommerce_add_to_cart', [$this, 'addCart']);
        add_action('woocommerce_remove_cart_item', [$this, 'removeCart'], 10, 2 );
    }

    /**
     * Function to record the action of inserting the product in the shopping cart
     * and obtaining the delivery class fee data.
     *
     * @return bool
     */
    public function addCart()
    {
        if(empty($_POST['product_id']) && empty($_POST['add-to-cart'])) {
            return false;
        }

        $productId = (!empty($_POST['product_id']))
            ? $_POST['product_id']
            : $_POST['add-to-cart'];

        $dataShipping = (new ShippingClassDataByProductService())
            ->get($productId);    

        $additionQuotationService = new AdditionalQuotationService();
        
        foreach ($dataShipping as $instanceId => $item) {
            if (!isset($item['additional_tax']) ||
                !isset($item['additional_time']) ||
                !isset($item['percent_tax'])) {
                continue;
            }

            $additionQuotationService->register(
                $productId,
                $instanceId,
                $item['additional_tax'],
                $item['additional_time'],
                $item['percent_tax']
            );
        }
    }

    /**
     * Function to remove data from delivery classes when the product is removed
     * from the shopping cart.
     *
     * @param $cart_item_key
     * @param $cart
     */
    public function removeCart($cart_item_key, $cart)
    {
        foreach($cart->cart_contents as $key => $item) {
            if ($key === $cart_item_key) {
                (new AdditionalQuotationService())
                    ->removeItem($item['product_id']);
            }
        }
    }
}
