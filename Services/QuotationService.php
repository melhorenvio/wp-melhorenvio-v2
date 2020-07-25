<?php

namespace Services;

use Models\Option;
use Services\ShippingMelhorEnvioService;

class QuotationService
{

    public function __construct()
    {
        session_start();    
    }

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
            'products' => $products
        ];

        $hash = $this->makeHashQuotation($body);

        $quotation = $this->getQuotationIfExistsSession($hash, $service);

        if (!$quotation) {

            $quotation = (new RequestService())->request(
                self::ROUTE_API_MELHOR_CALCULATE, 
                'POST', 
                $body,
                true
            );

            $this->storeQuotationSession($hash, $quotation);
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
    public function calculateQuotationByPackages($packages, $postal_code, $service = null)
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
            'packages' => $packages
        ];

        $hash = $this->makeHashQuotation($body);

        $quotation = $this->getQuotationIfExistsSession($hash, $service);

        if (!$quotation) {

            $quotation = (new RequestService())->request(
                self::ROUTE_API_MELHOR_CALCULATE, 
                'POST', 
                $body,
                true
            );

            $this->storeQuotationSession($hash, $quotation);
        }



        return $quotation;
    }

    /**
     * Function to save response quotation on session.
     *
     * @param string $hash
     * @param array $quotation
     * @return void
     */
    private function storeQuotationSession($hash, $quotation)
    {

        $_SESSION['quotation'][$hash] = $quotation;
        $_SESSION['quotation'][$hash]['created'] = date('Y-m-d h:i:s');
    }

    /**
     * Function to search for the quotation of a shipping service in the session, if it does not find false returns
     *
     * @param string $hash
     * @param int $service
     * @return bool|array
     */
    private function getQuotationIfExistsSession($hash, $service)
    {
    
        if (!isset($_SESSION['quotation'][$hash][$service])) {
            return false;
        }

        if ($this->isUltrapassedQuotation($hash)) {
            return false;
        }

        return $_SESSION['quotation'][$hash][$service];

    }

    /**
     * Function to create a hash based on quote parameters
     *
     * @param array $bodyQuotation
     * @return string
     */
    private function makeHashQuotation($bodyQuotation)
    {
        return md5(json_encode($bodyQuotation));
    }

    /**
     * Function to see if the session quote should expire due to the time
     *
     * @param string $hash
     * @return boolean
     */
    private function isUltrapassedQuotation($hash)
    {   
            
        $created = $_SESSION['quotation'][$hash]['created'];

        $dateLimit = date("Y-m-d h:i:s",strtotime(date("Y-m-d h:i:s")." -30 minutes"));

        if ($dateLimit > $created) {

            unset($_SESSION['quotation'][$hash]);

            return true;
        }

        return false;

    }
}