<?php

namespace Services;

use Models\Method;
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
        $payload = $this->createPayloadCheckoutOrder($postId);
        if (!empty($payload)) {
            $payload = (new Payload())->save($postId, $payload);
        }
    }

    /**
     * function to payload after finishied order in woocommerce.
     *
     * @param int $postId
     * @return object
     */
    public function createPayloadCheckoutOrder($postId)
    {
        $products = (new OrdersProductsService())->getProductsOrder($postId);
        $buyer = (new BuyerService())->getDataBuyerByOrderId($postId);
        $seller = (new SellerService())->getData();
        $options = (new Option())->getOptions();
        $productService = new ProductsService();
        $productsFilter = $productService->filter($products);
        $serviceId = (new Method($postId))->getMethodShipmentSelected($postId);

        $useInsuranceValue = (new CalculateShippingMethodService())->isCorreios($serviceId)
            ? $options->insurance_value
            : true;

        $body = (object) [
            'from' => (object) [
                'postal_code' => $seller->postal_code,
            ],
            'to' => (object) [
                'postal_code' => $buyer->postal_code
            ],
            'options' => (object) [
                'own_hand' => $options->own_hand,
                'receipt' => $options->receipt,
                'insurance_value' => $useInsuranceValue
            ],
            'products' => (object) $productsFilter,
            'service_selected' => $serviceId,
            'created' => date('Y-m-d h:i:s')
        ];

        return $body;
    }

    /**
     * function to create product-based payload
     *
     * @param string $to
     * @param array $products
     * @return object
     */
    public function createPayloadByProducts($postalCode, $products)
    {
        $seller = (new SellerService())->getData();

        $options = (new Option())->getOptions();

        $productService = new ProductsService();

        $productsFilter = $productService->filter($products);

        $payload =  (object) [
            'from' => (object) [
                'postal_code' => $seller->postal_code,
            ],
            'to' => (object) [
                'postal_code' => $postalCode
            ],
            'options' => (object) [
                'own_hand' => $options->own_hand,
                'receipt' => $options->receipt,
                'insurance_value' => true
            ],
            'products' => (object) $productsFilter
        ];

        return $payload;
    }

    /**
     * function to remove options from the insured amount of the payload
     *
     * @param object $payload
     * @return object
     */
    public function removeInsuranceValue($payload)
    {
        $payload->products = (new ProductsService())->removePrice((array) $payload->products);
        $payload->options->insurance_value = false;
        $payload->services = implode(CalculateShippingMethodService::SERVICES_CORREIOS, ",");

        return $payload;
    }
}
