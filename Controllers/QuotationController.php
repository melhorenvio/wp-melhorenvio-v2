<?php

namespace Controllers;

use Helpers\DimensionsHelper;
use Helpers\OptionsHelper;
use Helpers\TimeHelper;
use Helpers\MoneyHelper;
use Services\CalculateShippingMethodService;
use Services\LocationService;
use Services\QuotationService;
use Services\QuotationProductPageService;


/**
 * Class responsible for the quotation controller
 */
class QuotationController
{
    /**
     * Construct of CotationController
     */
    public function __construct()
    {
        add_action('woocommerce_checkout_order_processed', array(
            $this, 'makeCotationOrder'
        ));
    }

    /**
     * Function to make a quotation by order woocommerce
     *
     * @param int $orderId
     * @return void
     */
    public function makeCotationOrder($orderId)
    {
        $result = (new QuotationService())->calculateQuotationByOrderId($orderId);

        unset($_SESSION['quotation']);

        return $result;
    }

    /**
     * Function to refresh quotation
     *
     * @return json
     */
    public function refreshCotation()
    {
        $results = $this->makeCotationOrder($_GET['id']);
        return wp_send_json(
            $results,
            200
        );
    }

    /**
     * Function to perform the quotation on the product calculator
     *
     * @return json
     */
    public function cotationProductPage()
    {
        $data = $_POST['data'];

        $this->isValidRequest($data);

        $rates = (new QuotationProductPageService(
            $data['id_produto'], 
            $data['cep_origem'], 
            $data['quantity'])
        )->getRatesShipping();

        if (!empty($rates['error'])) {
            return wp_send_json([
                'success' => false,
                'error' => $rates['error']
            ], 400);
        }

        return wp_send_json([
            'success' => true,
            'data' => $rates
        ], 200);
    }

    /**
     * Function to validate request in screen product
     *
     * @param array $data
     * @return json
     */
    private function isValidRequest($data)
    {
        if (!isset($data['cep_origem'])) {
            return wp_send_json([
                'success' => false, 'message' => 'Infomar CEP de origem'
            ], 400);
        }

        if (!isset($data['produto_altura'])) {
            return wp_send_json([
                'success' => false,
                'message' => 'Infomar a altura do produto'
            ], 400);
        }

        if (!isset($data['produto_largura'])) {
            return wp_send_json([
                'success' => false,
                'message' => 'Infomar a largura do produto'
            ], 400);
        }

        if (!isset($data['produto_comprimento'])) {
            return wp_send_json([
                'success' => false,
                'message' => 'Infomar o comprimento do produto'
            ], 400);
        }

        if (!isset($data['produto_peso'])) {
            return wp_send_json([
                'success' => false,
                'message' => 'Infomar o peso do produto'
            ], 400);
        }

        if (!isset($data['produto_preco'])) {
            return wp_send_json([
                'success' => false,
                'message' => 'Infomar o preço do produto'
            ], 400);
        }
    }

    /**
     * @param [type] $package
     * @param [type] $services
     * @param [type] $to
     * @param array $options
     * @return void
     */
    public function makeCotationPackage($package, $services, $to, $options = [])
    {
        return $this->makeCotation($to, $services, [], $package, $options, false);
    }
}

$quotationController = new QuotationController();
