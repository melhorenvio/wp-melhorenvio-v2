<?php

namespace Controllers;

use Helpers\DimensionsHelper;
use Helpers\OptionsHelper;
use Helpers\TimeHelper;
use Helpers\MoneyHelper;
use Services\CalculateShippingMethodService;
use Services\LocationService;
use Services\QuotationService;

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

        $cep_origem = preg_replace('/\D/', '', $data['cep_origem']);

        $data['cep_origem'] = str_pad($cep_origem, 8, '0', STR_PAD_LEFT);

        $this->isValidRequest($data);

        $destination = (new LocationService())->getAddressByPostalCode($data['cep_origem']);

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

        $product = wc_get_product($data['id_produto']);

        $shipping_class_id = 0;

        if ($product) {
            $shipping_class_id = $product->get_shipping_class_id();
        }

        if (!empty($data['shipping_class_id'])) {
            $shipping_class_id = $data['shipping_class_id'];
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
                    'shipping_class_id' => $shipping_class_id,
                    'weight' => floatval($data['produto_peso']),
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

        $shippingZone = \WC_Shipping_Zones::get_zone_matching_package($package);

        $shippingMethods = $shippingZone->get_shipping_methods(true);

        if ($product) {
            $productShippingClassId = $product->get_shipping_class_id();

            if ($productShippingClassId) {
                foreach ($shippingMethods as $key => $method) {
                    if (empty($method->instance_settings['shipping_class_id'])) {
                        continue;
                    }

                    if ($method->instance_settings['shipping_class_id'] == CalculateShippingMethodService::ANY_DELIVERY) {
                        continue;
                    }

                    if ($productShippingClassId != $method->instance_settings['shipping_class_id']) {
                        unset($shippingMethods[$key]);
                    }
                }
            }
        }

        if (count($shippingMethods) == 0) {
            return wp_send_json([
                'success' => false,
                'message' => 'Não é feito envios para o CEP informado'
            ], 401);
        }

        $rates = array();

        foreach ($shippingMethods as $shippingMethod) {
            $rate = $shippingMethod->get_rates_for_package($package);

            if (empty($rate)) {
                continue;
            }

            $rate = end($rate);

            $rates[] = [
                'id' => $shippingMethod->id,
                'name' => $shippingMethod->title,
                'price' => $rate->meta_data['price'],
                'delivery_time' => $rate->meta_data['delivery_time'],
            ];
        }

        $rates = $this->orderingRatesByPrice($rates);

        return wp_send_json([
            'success' => true,
            'data' => $rates
        ], 200);
    }

    /**
     * Function to sort the rates by price
     *
     * @param array $quotation
     * @return array
     */
    public function orderingRatesByPrice($rates)
    {
        uasort($rates, function ($a, $b) {
            if ($a == $b) return 0;
            return ($a['price'] < $b['price']) ? -1 : 1;
        });

        return array_values($rates);
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
