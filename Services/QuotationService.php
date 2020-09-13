<?php

namespace Services;

use Models\Option;
use Models\Payload;

/**
 * Class responsible for the quotation service with the Melhor Envio api.
 */
class QuotationService
{
    const ROUTE_API_MELHOR_CALCULATE = '/shipment/calculate';

    /**
     * function to calculate quotation.
     *
     * @param object $body
     * @param bool $useInsuranceValue
     * @return array
     */
    public function calculate($payload, $useInsuranceValue)
    {
        $requestService = new RequestService();

        $quotations = $requestService->request(
            self::ROUTE_API_MELHOR_CALCULATE,
            'POST',
            $payload,
            true
        );

        if (!$useInsuranceValue) {
            $payload = (new PayloadService())->removeInsuranceValue($payload);
            $quotsWithoutValue = $requestService->request(
                self::ROUTE_API_MELHOR_CALCULATE,
                'POST',
                $payload,
                true
            );

            $quotations = array_merge($quotations, $quotsWithoutValue);

            $quotations = $this->setKeyQuotationAsServiceid($quotations);
        }

        return $quotations;
    }

    /**
     * function to set each key of array as service id
     *
     * @param array $quotations
     * @return array
     */
    private function setKeyQuotationAsServiceid($quotations)
    {
        $response = [];
        foreach ($quotations as $quotation) {
            $response[$quotation->id] = $quotation;
        }
        return $response;
    }

    /**
     * Function to calculate a quotation by post_id.
     *
     * @param int $postId
     * @return object $quotation
     */
    public function calculateQuotationByPostId($postId)
    {
        $payload = (new Payload())->get($postId);

        if (empty($payload)) {
            $products = (new OrdersProductsService())->getProductsOrder($postId);
            $buyer = (new BuyerService())->getDataBuyerByOrderId($postId);
            $payload = (new PayloadService())->createPayloadByProducts(
                $buyer->postal_code,
                $products
            );
        }

        $quotations = $this->calculate(
            $payload,
            $payload->options->insurance_value
        );

        return (new OrderQuotationService())->saveQuotation($postId, $quotations);
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

        $payload = (new PayloadService())->createPayloadByProducts(
            $postalCode,
            $products
        );

        $options = (new Option())->getOptions();

        $quotation = $this->getSessionCachedQuotation($payload, $service);

        if (!$quotation) {
            $quotation = $this->calculate($payload, $options->insurance_value);
            $this->storeQuotationSession($payload, $quotation);
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
        $_SESSION['quotation'][$hash]->created = date('Y-m-d H:i:s');
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
}
