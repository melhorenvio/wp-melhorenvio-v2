<?php

namespace Services;

use Helpers\DimensionsHelper;

class TestService
{
    protected $version;

    public function __construct($version)
    {
        $this->version = $version;
    }

    public function run()
    {
        (new SessionService())->clear();

        $response = [
            'version' => $this->version,
            'php' => phpversion(),
            'environment' => (new TokenService())->check(),
            'user' => $this->hideDataMe((new SellerService())->getData()),
            'metrics' => $this->getMetrics(),
            'path' => dirname(__FILE__)
        ];

        if (isset($_GET['postalcode'])) {

            $product = $this->getProductToTest();

            $quotation = (new QuotationService())->calculateQuotationByProducts(
                $product,
                $_GET['postalcode'],
                null
            );

            $response['product'] = $product;

            foreach ($quotation as  $item) {
                $response['quotation'][$item->id] = [
                    "Serviço" => $item->name,
                    "Valor" => $item->price,
                    'Erro' => $item->error
                ];
            }
        }

        echo json_encode($response);
        die;
    }

    /**
     * Function to get cep destiny
     *
     * @param array data
     * @return string $cep
     */
    private function cepDestiny($data)
    {
        return (isset($data['cep'])) ? $data['cep'] : '01018020';
    }

    /**
     * Function to get packages
     *
     * @param array data
     * @return array $packages
     */
    private function packages($data)
    {
        return [
            'width'  => (isset($data['width']))  ? (float) $data['width']  : 17,
            'height' => (isset($data['height'])) ? (float) $data['height'] : 23,
            'length' => (isset($data['length'])) ? (float) $data['length'] : 10,
            'weight' => (isset($data['weight'])) ? (float) $data['weight'] : 1
        ];
    }

    /**
     * Function to get insurance vale
     *
     * @param array data
     * @return float $insurance_value
     */
    private function insuranceValue($data)
    {
        return (isset($data['insurance_value']))  ? (float) $data['insurance_value']  : 20.50;
    }

    /**
     * Function to get a list of plugins instaleds
     *
     * @return array $plugins
     */
    private function getListPluginsInstaleds()
    {
        return apply_filters('network_admin_active_plugins', get_option('active_plugins'));
    }

    /**
     * Function to get a list of methods shipments
     *
     * @return array $shipping_methods
     */
    private function getShippingServices()
    {
        $services = [];
        foreach (glob(ABSPATH . '/wp-content/plugins/melhor-envio-cotacao/services_methods/*.php') as $filename) {
            $services[] = $filename;
        }

        foreach (glob(ABSPATH . '/wp-content/plugins/plugin-woocommerce/services_methods/*.php') as $filename) {
            $services[] = $filename;
        }

        return $services;
    }

    /**
     * Function to extract any data
     *
     * @param object $user
     * @return array $data
     */
    private function hideDataMe($data)
    {
        return [
            'postal_code' => $data->postal_code,
            'email' => $data->email
        ];
    }

    private function getProductToTest()
    {
        $args = [];

        $products = wc_get_products($args);

        $_product = $products[rand(0, (count($products) - 1))];

        return [
            "id"              => $_product->get_id(),
            "name"            => $_product->get_name(),
            "quantity"        => 1,
            "unitary_value"   => round($_product->get_price(), 2),
            "insurance_value" => round($_product->get_price(), 2),
            "weight"          => (new DimensionsHelper())->converterIfNecessary($_product->weight),
            "width"           => (new DimensionsHelper())->converterDimension($_product->width),
            "height"          => (new DimensionsHelper())->converterDimension($_product->height),
            "length"          => (new DimensionsHelper())->converterDimension($_product->length)
        ];
    }

    /**
     * Get metrics useds in woocommerce.
     *
     * @return array $metrics
     */
    private function getMetrics()
    {
        return [
            'weight_unit' => get_option('woocommerce_weight_unit'),
            'dimension_unit' => get_option('woocommerce_dimension_unit')
        ];
    }
}
