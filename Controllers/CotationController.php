<?php

namespace Controllers;

use Helpers\DimensionsHelper;
use Helpers\OptionsHelper;
use Helpers\TimeHelper;
use Helpers\MoneyHelper;
use Services\QuotationService;

/**
 * Class responsible for the quotation controller
 */
class CotationController
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
     * @param [type] $order_id
     * @return void
     */
    public function makeCotationOrder($order_id)
    {
        $result = (new QuotationService())->calculateQuotationByOrderId($order_id);

        $totalCart = 0;
        $freeShipping = false;

        foreach (WC()->cart->cart_contents as $cart) {
            $totalCart += $cart['line_subtotal'];
        }

        // Utilizado frete grátis?
        foreach (WC()->cart->get_coupons() as $cp) {
            if ($cp->get_free_shipping() && $totalCart >= $cp->amount) {
                $freeShipping = true;
            }
        }

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
        return wp_send_json($results, 200);
    }

    /**
     * Function to perform the quotation on the product calculator
     * 
     * @return json
     */
    public function cotationProductPage()
    {
        $cep_origem = str_replace("-", "", $_POST['data']['cep_origem']);

        $_POST['data']['cep_origem'] = str_pad($cep_origem, 8, '0', STR_PAD_LEFT);

        $this->isValidRequest($_POST['data']);

        $destination = $this->getAddressByCep($_POST['data']['cep_origem']);

        if (empty($destination) || is_null($destination)) {
            return wp_send_json([
                'success' => false,
                'message' => 'CEP inválido ou não encontrado'
            ], 404);
        }

        if (!isset($destination->cep) || !isset($destination->uf)) {
            return wp_send_json([
                'success' => false,
                'message' => 'CEP inválido ou não encontrado'
            ], 404);
        }

        $dimensionHelper = new DimensionsHelper();

        $package = array(
            'ship_via'     => '',
            'destination'  => array(
                'country'  => 'BR',
                'state'    => $destination->uf,
                'postcode' => $destination->cep,
            ),
            'cotationProduct' => array(
                (object) array(
                    'id' => $_POST['data']['id_produto'],
                    "weight" => $dimensionHelper->convertWeightUnit(
                        floatval($_POST['data']['produto_peso'])
                    ),
                    "width" => $dimensionHelper->convertUnitDimensionToCentimeter(
                        floatval($_POST['data']['produto_largura'])
                    ),
                    "length" => $dimensionHelper->convertUnitDimensionToCentimeter(
                        floatval($_POST['data']['produto_comprimento'])
                    ),
                    "height" => $dimensionHelper->convertUnitDimensionToCentimeter(
                        floatval($_POST['data']['produto_altura'])
                    ),
                    'quantity' => intval($_POST['data']['quantity']),
                    'price' => floatval(
                        $_POST['data']['produto_preco']
                    ),
                    'insurance_value'    => floatval(
                        $_POST['data']['produto_preco']
                    ),
                    'notConverterWeight' => true
                )
            )
        );

        $shipping_zone = \WC_Shipping_Zones::get_zone_matching_package($package);
        $shipping_methods = $shipping_zone->get_shipping_methods(true);
        if (count($shipping_methods) == 0) {
            return wp_send_json([
                'success' => false,
                'message' => 'Não é feito envios para o CEP informado'
            ], 401);
        }

        $rates = array();
        $free = 0;

        foreach ($shipping_methods as $shipping_method) {
            $rate = $shipping_method->get_rates_for_package($package);
            if (key($rate) == 'free_shipping') {
                $free++;
            }

            if (empty($rate) || (key($rate) == 'free_shipping') && $free > 1) {
                continue;
            }

            $rates[] = $this->mapObject($rate[key($rate)]);
        }

        return wp_send_json([
            'success' => true,
            'data' => $rates
        ], 200);
    }

    /**
     * Check is has all data to make quotation.
     *
     * @param Array $request
     * @return void|json
     */
    private function isValidRequest($request)
    {
        if (!isset($request)) {
            return wp_send_json([
                'success' => false,
                'message' => 'Dados incompletos'
            ], 400);
        }

        if (!isset($request['cep_origem'])) {
            return wp_send_json([
                'success' => false,
                'message' => 'Campo CEP é necessário'
            ], 400);
        }

        if (strlen(trim($request['cep_origem'])) != 8) {
            return wp_send_json([
                'success' => false,
                'message' => 'Campo CEP precisa ter 8 digitos'
            ], 400);
        }

        if (!isset($request['produto_peso'])) {
            return wp_send_json([
                'success' => false,
                'message' => 'Informar o peso do produto'
            ], 400);
        }

        if (!isset($request['produto_largura'])) {
            return wp_send_json([
                'success' => false,
                'message' => 'Informar  largura do produto'
            ], 400);
        }

        if (!isset($request['produto_comprimento'])) {
            return wp_send_json([
                'success' => false,
                'message' => 'Informar o comprimento do produto'
            ], 400);
        }

        if (!isset($request['produto_altura'])) {
            return wp_send_json([
                'success' => false,
                'message' => 'Informar a altura do produto'
            ], 400);
        }

        if (!isset($request['produto_preco'])) {
            return wp_send_json([
                'success' => false,
                'message' => 'Informar o preço do produto'
            ], 400);
        }
    }

    /**
     * Get address information from zip code
     *
     * @param string $cep
     * @return Json
     */
    private function getAddressByCep($cep)
    {
        if (empty($cep)) return null;

        $url = "https://location.melhorenvio.com.br/" . str_replace('-', '', trim($cep));

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        $result = curl_exec($curl);
        $error  = curl_error($curl);
        curl_close($curl);

        if (!empty($error)) return null;

        $response = json_decode($result);
        return $response;
    }

    /**
     * @param [type] $item
     * @return void
     */
    private function mapObject($item)
    {
        $name = null;
        if (isset($item->meta_data['name'])) {
            $name = $item->meta_data['name'];
        }

        $company = null;
        if (isset($item->meta_data['company'])) {
            $company = $item->meta_data['company'];
        }

        $method = (new optionsHelper())->getName(
            $item->get_id(),
            $name,
            $company,
            $item->get_label()
        );

        return [
            'id' => $item->get_id(),
            'name' => $method['method'],
            'price' => (new MoneyHelper())->setLabel(
                $item->get_cost(),
                $item->get_id()
            ),
            'company' => $method['company'],
            'delivery_time' => (new TimeHelper)->setLabel(
                $item->meta_data['delivery_time'],
                $item->get_id()
            ),
            'added_extra' => false
        ];
    }
}

$cotationcontroller = new CotationController();
