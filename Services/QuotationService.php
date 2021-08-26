<?php

namespace Services;

use Models\Option;
use Models\Payload;
use Helpers\TimeHelper;
use Helpers\SessionHelper;
use Services\PayloadService;

/**
 * Class responsible for the quotation service with the Melhor Envio api.
 */
class QuotationService
{
    const ROUTE_API_MELHOR_CALCULATE = '/shipment/calculate';

    const TIME_DURATION_SESSION_QUOTATION_IN_SECONDS = 900;

    /**
     * function to calculate quotation.
     *
     * @param object $body
     * @param bool $useInsuranceValue
     * @return array
     */
    public function calculate($payload, $useInsuranceValue)
    {
        if (empty($payload)) {
            return false;
        }

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

            if (is_array($quotations) && is_array($quotsWithoutValue)) {
                $quotations = array_merge($quotations, $quotsWithoutValue);
                $quotations = $this->setKeyQuotationAsServiceid($quotations);
            }
        };

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
     * @return array $quotation
     */
    public function calculateQuotationByPostId($postId)
    {
        $payload  = (new Payload())->get($postId);

        if (empty($payload)) {
            $products = (new OrdersProductsService())->getProductsOrder($postId);
            $buyer = (new BuyerService())->getDataBuyerByOrderId($postId);
            $payload = (new PayloadService())->createPayloadByProducts(
                $buyer->postal_code,
                $products
            );
        }

        if (!(new PayloadService())->validatePayload($payload)) {
            return false;
        }

        $quotations = $this->calculate(
            $payload,
            (isset($payload->options->use_insurance_value))
                ? $payload->options->use_insurance_value
                : false
        );

        return (new OrderQuotationService())->saveQuotation($postId, $quotations);
    }

    /**
     * Function to calculate a quotation by products.
     *
     * @param array $products
     * @param string $postalCode
     * @param int $service
     * @return array|false|object
     */
    public function calculateQuotationByProducts(
        $products,
        $postalCode,
        $service = null
    ) {

        SessionHelper::initIfNotExists();

        $payload = (new PayloadService())->createPayloadByProducts(
            $postalCode,
            $products
        );
        if (empty($payload)) {
            return false;
        }

        $hash = $this->generateHashQuotation($payload);
      
        $options = (new Option())->getOptions();

        $quotationsCachead = $this->getSessionCachedQuotation($hash, $service);

        if (empty($quotationsCachead)) {
            $quotations =  $this->calculate($payload, $options->insurance_value);
            $this->storeQuotationSession($hash, $quotations);
            return $quotations;
        }

        if (!empty($quotationsCachead) && empty($service)) {
            return $quotationsCachead;
        }

        if (!empty($quotationsCachead) && !empty($service)) {
            $quotationCachead = null;
            $quotationsCachead = $this->setKeyQuotationAsServiceid($quotationsCachead);
            foreach ($quotationsCachead as $quotation) {
                if ($quotation->id == $service) {
                    $quotationCachead = $quotation;
                }
            }

            if (!empty($quotationCachead)) {
                return $quotationCachead;
            }
        }

        return $quotationsCachead;
    }


    /**
     * Function to save response quotation on session.
     *
     * @param array $bodyQuotation
     * @param array $quotation
     * @return void
     */
    private function storeQuotationSession($hash, $quotation)
    {
        $quotationSession[$hash]['quotations'] = $quotation;
        $quotationSession[$hash]['created'] = date('Y-m-d H:i:s');

        $_SESSION['quotation-melhor-envio'][$hash] = [
            'quotations' => $quotation,
            'created' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Function to search for the quotation of a shipping service in the session,
     * if it does not find false returns
     *
     * @param array $bodyQuotation
     * @param int $service
     * @return bool|array
     */
    private function getSessionCachedQuotation($hash, $service)
    {
        $session = $_SESSION;

        if (empty($session['quotation-melhor-envio'][$hash])) {
            return false;
        }

        $quotationsCachead = $session['quotation-melhor-envio'][$hash];
        $dateCreated = $quotationsCachead['created'];
        $quotationsCachead = $quotationsCachead['quotations'];

        if (!empty($dateCreated)) {
            if ($this->isUltrapassedQuotation($dateCreated)) {
                unset($session['quotation-melhor-envio'][$hash]);
                $_SESSION = $session;
            }
        }

        return $quotationsCachead;
    }

    private function isUltrapassedQuotation($dateQuotation)
    {
        return TimeHelper::howSecondsInPast($dateQuotation) > self::TIME_DURATION_SESSION_QUOTATION_IN_SECONDS;
    }

    /**
     * function to created a hash by quotation.
     *
     * @param object $payload
     * @return string
     */
    private function generateHashQuotation($payload)
    {
        $products = [];

        if (!empty($payload->products)) {
            foreach ($payload->products as $product) {
                $products[] = [
                    'id' => $product->id,
                    'width' => $product->width,
                    'height' => $product->height,
                    'length' => $product->length,
                    'weight' => $product->weight,
                    'unitary_value' => $product->unitary_value,
                    'quantity' => $product->quantity,
                ];
            }
        }
        
        return md5(json_encode([
            'from' => $payload->from->postal_code,
            'to' => $payload->to->postal_code,
            'options' => [
                'own_hand' => $payload->options->own_hand,
                'receipt' => $payload->options->receipt,
            ],
            'products' => $products
        ]));
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

        if (!is_array($quotation)) {
            return $quotation;
        }

        uasort($quotation, function ($a, $b) {
            if ($a == $b) return 0;
            if (!isset($a->price) || !isset($b->price)) return 0;
            return ($a->price < $b->price) ? -1 : 1;
        });
        return $quotation;
    }
}
