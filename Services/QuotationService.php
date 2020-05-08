<?php

namespace Services;

class QuotationService
{

    const ROUTE_API_MELHOR_CALCULATE = '/shipment/calculate';

    /**
     * Function to calculate a quotation by order_id.
     *
     * @param int $order_id
     * @return object $quotation
     */
    public function calculateQuotationByOrderId($order_id)
    {
        $orderProductsService = new OrdersProductsService();

        $products = $orderProductsService->getProductsOrder($order_id);

        $buyerService = new BuyerService();

        $buyer = $buyerService->getDataBuyerByOrderId($order_id);

        return $this->calculateQuotationByProducts($products, $buyer->postal_code);

    }

    /**
     * Function to calculate a quotation by products.
     *
     * @param array $products
     * @param string $postal_code
     * @return object $quotation
     */
    public function calculateQuotationByProducts($products, $postal_code)
    {   
        $sellerService = new SellerService();

        $seller = $sellerService->getData();

        $body = [
            'from' => [
                'postal_code' => $seller->postal_code,
            ],
            'to' => [
                'postal_code' => $postal_code
            ],
            'options' => [
                'receipt' => false, //TODO
                'own_hand' => false //TODO
            ],
            'services' => '1,2,3,4,17', //TODO
            'products' => $products
        ];

        $requestService = new RequestService();

        return $requestService->request(
            self::ROUTE_API_MELHOR_CALCULATE, 
            'POST', 
            $body
        );
    }
}