<?php

namespace Services;

use Models\Option;
use Models\Payload;

class PayloadService
{
    /**
     * Function to save payload
     *
     * @param int $postId
     * @return void
     */
    public function save($postId)
    {
        $payload = $this->mount($postId);
        if (!empty($payload)) {
            $payload = (new Payload())->save($postId, $payload);
        }
    }

    /**
     * function to mount payload
     *
     * @param int $postId
     * @return array
     */
    public function mount($postId)
    {
        $products = (new OrdersProductsService())->getProductsOrder($postId);

        $buyer = (new BuyerService())->getDataBuyerByOrderId($postId);

        $seller = (new SellerService())->getData();

        $options = (new Option())->getOptions();

        $productService = new ProductsService();

        $productsFilter = $productService->filter($products);

        return (object) [
            'from' => (object) [
                'postal_code' => $seller->postal_code,
            ],
            'to' => (object) [
                'postal_code' => $buyer->postal_code
            ],
            'options' => (object) [
                'own_hand' => $options->own_hand,
                'receipt' => $options->receipt,
                'insurance_value' => $options->insurance_value //dúvida, antes sempre true.
            ],
            'products' => (object) $productsFilter,
            'created' => date('Y-m-d h:i:s')
        ];
    }

    public function mountByProducts($from, $to, $products, $options, $service)
    {
        return [
            'from' => (object) [
                'postal_code' => $from,
            ],
            'to' => (object) [
                'postal_code' => $to
            ],
            'options' => (object) [
                'own_hand' => $options->own_hand,
                'receipt' => $options->receipt,
                'insurance_value' => (new CalculateShippingMethodService())->insuranceValueIsRequired($options->insurance_value, $service)
            ],
            'products' => (object) $products
        ];
    }
}
