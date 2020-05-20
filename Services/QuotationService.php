<?php

namespace Services;

use Models\Option;
use Services\ShippingMelhorEnvioService;

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
        $products = (new OrdersProductsService())->getProductsOrder($order_id);

        $buyer = (new BuyerService())->getDataBuyerByOrderId($order_id);

        $quotation = $this->calculateQuotationByProducts($products, $buyer->postal_code, null);

        return (new OrderQuotationService())->saveQuotation($order_id, $quotation);
    }

    /**
     * Function to calculate a quotation by products.
     *
     * @param array $products
     * @param string $postal_code
     * @return object $quotation
     */
    public function calculateQuotationByProducts($products, $postal_code, $service = null)
    {   
        $seller = (new SellerService())->getData();
            
        $body = [
            'from' => [
                'postal_code' => $seller->postal_code,
            ],
            'to' => [
                'postal_code' => $postal_code
            ],
            'options'  => (new Option())->getOptions(),
            'services' => (!is_null($service)) ? $service : (new ShippingMelhorEnvioService())->getStringCodesEnables(),
            'products' => $products
        ];

        $result = (new RequestService())->request(
            self::ROUTE_API_MELHOR_CALCULATE, 
            'POST', 
            $body,
            true
        );

        return $result;
    }
}