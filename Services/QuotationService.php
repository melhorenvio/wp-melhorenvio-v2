<?php

namespace Services;

use Models\Option;

/**
 * Class responsible for the quotation service with the Melhor Envio api.
 */
class QuotationService
{
    const ROUTE_API_MELHOR_CALCULATE = '/shipment/calculate';

    const PERCENT_INSURANCE_VALUE = 5;

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

        $seller = (new SellerService())->getData();

        $options = (new Option())->getOptions();

        $productService = new ProductsService();

        $productsFilter = $productService->filter($products);

        $body = [
            'from' => [
                'postal_code' => $seller->postal_code,
            ],
            'to' => [
                'postal_code' => $buyer->postal_code
            ],
            'options' => [
                'own_hand' => $options->own_hand,
                'receipt' => $options->receipt,
                'insurance_value' => true
            ],
            'products' => $productsFilter,
        ];

        $quotations = (new RequestService())->request(
            self::ROUTE_API_MELHOR_CALCULATE,
            'POST',
            $body,
            true
        );

        if (!$options->insurance_value) {
            $body['products'] = $productService->removePrice($productsFilter);
            $body['options']['insurance_value'] = false;
            $body['services'] = implode(CalculateShippingMethodService::SERVICES_CORREIOS, ',');

            $quotationWithoutInsurance = (new RequestService())->request(
                self::ROUTE_API_MELHOR_CALCULATE,
                'POST',
                $body,
                true
            );

            $quotations = array_merge($quotations, $quotationWithoutInsurance);
        }

        return (new OrderQuotationService())->saveQuotation($orderId, $quotations);
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

        $productsFilter = $productService->filter($products);

        $shippingMethodService = new CalculateShippingMethodService();

        if (!$shippingMethodService->insuranceValueIsRequired($options->insurance_value, $service)) {
            $productsFilter = $productService->removePrice($productsFilter);
        }

        $body = [
            'from' => [
                'postal_code' => $seller->postal_code,
            ],
            'to' => [
                'postal_code' => $postalCode
            ],
            'options' => [
                'own_hand' => $options->own_hand,
                'receipt' => $options->receipt,
                'insurance_value' => $shippingMethodService->insuranceValueIsRequired($options->insurance_value, $service)
            ],
            'products' => $productsFilter
        ];

        $quotation = $this->getSessionCachedQuotation($body, $service);

        if (!$quotation) {
            $quotation = (new RequestService())->request(
                self::ROUTE_API_MELHOR_CALCULATE,
                'POST',
                $body,
                true
            );

            if (!empty($quotation->errors)) {
                return $quotation->errors;
            }

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
                'own_hand' => $options->own_hand,
                'receipt' => $options->receipt
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

            if (!empty($quotation->errors)) {
                return false;
            }

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

        if (isset($_SESSION['quotation'][$hash]->success) && !$_SESSION['quotation'][$hash]->success) {
            return true;
        }

        if (empty($_SESSION['quotation'][$hash]['created'])) {
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

    /**
     * Function to check if the quotation is Correios and has errors
     *
     * @param object $quotation
     * @return boolean
     */
    private function isCorreiosWithErrors($quotation)
    {
        $calculateService = new CalculateShippingMethodService();

        return (!empty($quotation->error) || !$calculateService->isCorreios($quotation->id));
    }

    /**
     * Function to search for quotation Correios and to reject without insurance value
     *
     * @param array $quotations
     * @param array $products
     * @param object $buyer
     * 
     * @return array
     */
    private function findItemCorreiosForRecalculeQuotationWithoutInsurance($quotations, $products, $buyer)
    {
        $options = (new option())->getOptions();

        if (!$options->insurance_value) {
            foreach ($quotations as $key => $quotation) {

                if ($this->isCorreiosWithErrors($quotation)) {
                    continue;
                }

                $quotations[$key] = $this->calculateQuotationByProducts(
                    $products,
                    $buyer->postal_code,
                    $quotation->id
                );
            }
        }

        return $quotations;
    }
}
