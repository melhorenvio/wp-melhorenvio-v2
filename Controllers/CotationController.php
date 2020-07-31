<?php

namespace Controllers;

use Helpers\DimensionsHelper;
use Helpers\OptionsHelper;
use Helpers\TimeHelper;
use Helpers\MoneyHelper;
use Services\LocationService;
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

        $cep_origem = preg_replace('/\D/', '', $data['cep_origem']);

        $data['cep_origem'] = str_pad($cep_origem, 8, '0', STR_PAD_LEFT);

        $this->isValidRequest($data);

        $destination = (new LocationService())->getAddressByPostalCode($_POST['data']['cep_origem']);

        if (empty($destination)) {
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

        $package = array(
            'ship_via'     => '',
            'destination'  => array(
                'country'  => 'BR',
                'state'    => $destination->uf,
                'postcode' => $destination->cep,
            ),
            'cotationProduct' => array(
                (object) array(
                    'id' => $data['id_produto'],
                    'weight' => DimensionsHelper::convertWeightUnit(
                        floatval($data['produto_peso'])
                    ),
                    'width' => DimensionsHelper::convertUnitDimensionToCentimeter(
                        floatval($data['produto_largura'])
                    ),
                    'length' => DimensionsHelper::convertUnitDimensionToCentimeter(
                        floatval($data['produto_comprimento'])
                    ),
                    'height' => DimensionsHelper::convertUnitDimensionToCentimeter(
                        floatval($data['produto_altura'])
                    ),
                    'quantity' => intval($data['quantity']),
                    'price' => floatval(
                        $data['produto_preco']
                    ),
                    'insurance_value'    => floatval(
                        $data['produto_preco']
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

        $method = (new OptionsHelper())->getName(
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
