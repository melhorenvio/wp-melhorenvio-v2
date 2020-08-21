<?php

namespace Services;

use Models\Option;

/**
 * Class responsible for the quotation service with the Melhor Envio api.
 */
class QuotationService
{
    const ROUTE_API_MELHOR_CALCULATE = '/shipment/calculate';

    /**
     * Function to calculate a quotation by order_id.
     *
     * @param int $orderId
     * @return object $quotation
     */
    public function calculateQuotationByOrderId($orderId)
    {
        $products = (new OrdersProductsService())->getProductsOrder($orderId);

        $buyer = (new BuyerService())->getDataBuyerByOrderId($orderId);

        $quotation = $this->calculateQuotationByProducts(
            $products,
            $buyer->postal_code,
            null
        );

        return (new OrderQuotationService())->saveQuotation($orderId, $quotation);
    }

    /**
     * Function to calculate a quotation by products.
     *
     * @param array $products  
     * @param  string $postal_code
     * @param int $service
     * @return  object $quotation
     */
    public function calculateQuotationByProducts(
        $products,
        $postalCode,
        $service = null
    ) {

        $seller = (new SellerService())->getData();

        $options = (new Option())->getOptions();

        $productService = new ProductsService();  

        $shippingMethodService = new CalculateShippingMethodService();

        if (!$options->vs && !$shippingMethodService->isCorreios($service)) {
            $products = $productService->removePrice($products);
        }

        $body = [
            'from' => [
                'postal_code' => $seller->postal_code,
            ],
            'to' => [
                'postal_code' => $postalCode
            ],
            'options' => [
                'own_hand' => $options->mp,
                'receipt' => $options->ar,
                'insurance_value' => ($shippingMethodService->insuranceValueIsRequired($options->vs, $service)) 
                    ? $productService->getInsuranceValue($products) 
                    : 0,
            ],
            'products' => $products
        ];

        $quotation = $this->getSessionCachedQuotation($body, $service);

        if (!$quotation) {
            $quotation = (new RequestService())->request(
                self::ROUTE_API_MELHOR_CALCULATE,
                'POST',
                $body,
                true
            );

            $this->storeQuotationSession($body, $quotation);
        }

        return $quotation;
    }

    /**
     * Function to calculate a quotation by packages.
     *
     * @param array $packages
     * @param string $postal_code
     * @return object $quotation
     */
    public function calculateQuotationByPackages(
        $packages,
        $postalCode,
        $service = null
    ) {
        $seller = (new SellerService())->getData();

        $options = (new Option())->getOptions();

        $body = [
            'from' => [
                'postal_code' => $seller->postal_code,
            ],
            'to' => [
                'postal_code' => $postalCode
            ],
            'options' => [
                'own_hand' => $options->mp,
                'receipt' => $options->ar
            ],
            'packages' => $packages
        ];

        $quotation = $this->getSessionCachedQuotation($body, $service);

        if (!$quotation) {
            $quotation = (new RequestService())->request(
                self::ROUTE_API_MELHOR_CALCULATE,
                'POST',
                $body,
                true
            );

            $this->storeQuotationSession($body, $quotation);
        }

        return $quotation;
    }

    /**
     * Function to save response quotation on session.
     *
     * @param array $bodyQuotation
     * @param array $quotation
     * @return void
     */
    private function storeQuotationSession($bodyQuotation, $quotation)
    {
        if (!isset($_SESSION)) {
            session_start();
        }

        $quotation = $this->orderingQuotationByPrice($quotation);

        $hash = md5(json_encode($bodyQuotation));
        $_SESSION['quotation'][$hash] = $quotation;
        $_SESSION['quotation'][$hash]['created'] = date('Y-m-d H:i:s');
    }

    /**
     * Function to sort the quote by price
     *
     * @param array $quotation
     * @return array
     */
    public function orderingQuotationByPrice($quotation)
    {
        if (is_null($quotation)) {
            return $quotation;
        }

        uasort($quotation, function ($a, $b) {
            if ($a == $b) return 0;
            if (!isset($a->price) || !isset($b->price)) return 0;
            return ($a->price < $b->price) ? -1 : 1;
        });
        return $quotation;
    }

    /**
     * Function to search for the quotation of a shipping service in the session, 
     * if it does not find false returns
     *
     * @param array $bodyQuotation
     * @param int $service
     * @return bool|array
     */
    private function getSessionCachedQuotation($bodyQuotation, $service)
    {
        $hash = md5(json_encode($bodyQuotation));

        if (!isset($_SESSION)) {
            session_start();
        }

        if (!isset($_SESSION['quotation'][$hash])) {
            unset($_SESSION['quotation'][$hash]);
            return false;
        }

        if ($this->isSessionCachedQuotationExpired($bodyQuotation)) {
            return false;
        }

        $quotations = array_filter(
            $_SESSION['quotation'][$hash],
            function ($item) use ($service) {
                if (isset($item->id) && $item->id == $service) {
                    return $item;
                }
            }
        );

        if (!is_array($quotations)) {
            return false;
        }

        return end($quotations);
    }

    /**
     * Function to see if the session quote should expire due to the time
     *
     * @param array $bodyQuotation payload to make quotation on Melhor Envio api
     * @return boolean
     */
    private function isSessionCachedQuotationExpired($bodyQuotation)
    {
        $hash = md5(json_encode($bodyQuotation));

        if (!isset($_SESSION['quotation'][$hash]['created'])) {
            return true;
        }

        $created = $_SESSION['quotation'][$hash]['created'];

        $dateLimit = date('Y-m-d H:i:s', strtotime('-15 minutes'));

        if ($dateLimit > $created) {
            unset($_SESSION['quotation'][$hash]);
            return true;
        }

        return false;
    }
}
