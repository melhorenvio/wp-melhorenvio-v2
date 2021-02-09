<?php

namespace Services;

use Models\Method;
use Models\Option;
use Models\Payload;
use Models\ShippingService;

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
            (new Payload())->save($postId, $payload);
        }
    }

    /**
     * Function to return the payload data of the quote hiding customer data
     *
     * @param int $postId
     * @return object
     */
    public function getPayloadHideImportantData($postId)
    {
        $payload = (new Payload())->get($postId);

        unset($payload->seller);
        unset($payload->buyer);

        return $payload;
    }

    /**
     * function to payload after finishied order in woocommerce.
     *
     * @param int $postId
     * @return object
     */
    public function createPayloadCheckoutOrder($postId)
    {
        $order = new \WC_Order( $postId );
        $products = (new OrdersProductsService())->getProductsOrder($postId);
        $buyer = (new BuyerService())->getDataBuyerByOrderId($postId);
        $seller = (new SellerService())->getData();
        $options = (new Option())->getOptions();
        $productService = new ProductsService();
        $productsFilter = $productService->filter($products);
        $serviceId = (new Method($postId))->getMethodShipmentSelected($postId);

        return (object) [
            'from' => (object) [
                'postal_code' => $seller->postal_code,
            ],
            'to' => (object) [
                'postal_code' => $buyer->postal_code
            ],
            'services' => implode(",", ShippingService::getAvailableServices()),
            'options' => (object) [
                'own_hand' => $options->own_hand,
                'receipt' => $options->receipt,
                'insurance_value' => $order->get_subtotal(),
                'use_insurance_value' => $options->insurance_value
            ],
            'products' => (object) $productsFilter,
            'service_selected' => $serviceId,
            'seller' => $seller,
            'buyer' => $buyer,
            'units' => [
                'weight' => strtolower(get_option('woocommerce_weight_unit')),
                'dimension' => strtolower(get_option('woocommerce_dimension_unit'))
            ],
            'shipping_total' => $order->get_shipping_total(),
            'created' => date('Y-m-d h:i:s')
        ];
    }

    /**
     * function to create product-based payload
     *
     * @param string $postalCode
     * @param array $products
     * @return object
     */
    public function createPayloadByProducts($postalCode, $products)
    {
        $seller = (new SellerService())->getData();

        $options = (new Option())->getOptions();

        $productService = new ProductsService();

        $productsFilter = $productService->filter($products);

        return  (object) [
            'from' => (object) [
                'postal_code' => $seller->postal_code,
            ],
            'to' => (object) [
                'postal_code' => $postalCode
            ],
            'services' => implode(",", ShippingService::getAvailableServices()),
            'options' => (object) [
                'own_hand' => $options->own_hand,
                'receipt' => $options->receipt,
                'insurance_value' => $productService->getInsuranceValue($productsFilter),
                'use_insurance_value' => $options->insurance_value
            ],
            'products' => (object) $productsFilter
        ];
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
        $payload->options->insurance_value = 0;
        $payload->services = implode(
            ",",
            ShippingService::SERVICES_CORREIOS
        );

        return $payload;
    }
}
