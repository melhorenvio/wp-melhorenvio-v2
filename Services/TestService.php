<?php

namespace Services;

use Helpers\DimensionsHelper;
use Models\Option;
use Models\ResponseStatus;

class TestService
{
    const DEFAULT_QUANTITY_PRODUCT = 1;

    protected $version;

    public function __construct($version)
    {
        $this->version = $version;
    }

    public function run()
    {
        if (empty($_GET['hash'])) {
            return wp_send_json([
                'message' => 'Acesso não autorizado'
            ], ResponseStatus::HTTP_UNAUTHORIZED);
        }

        if (md5($_GET['hash']) != '22b0e1b5ac96f76652c82b13bb01e3c9') {
            return wp_send_json([
                'message' => 'Acesso não autorizado'
            ], ResponseStatus::HTTP_UNAUTHORIZED);
        }

        $response = [
            'version' => $this->version,
            'php' => phpversion(),
            'environment' => (new TokenService())->check(),
            'user' => $this->hideDataMe((new SellerService())->getData()),
            'metrics' => $this->getMetrics(),
            'path' => $this->getPluginsPath(),
            'options' => (new Option())->getOptions(),
            'plugins' => $this->getInstalledPlugins(),
            'shipping-methods' => $this->getShippingMethods()
        ];

        if (isset($_GET['postalcode'])) {
            $product[] = $this->getProductToTest();
            $quotation = (new QuotationService())->calculateQuotationByProducts(
                $product,
                $_GET['postalcode'],
                null
            );

            $response['product'] = $product;

            foreach ($quotation as $item) {
                $packages = [];
                if (!empty($item->packages)) {
                    foreach ($item->packages as $package) {
                        $packages[] = [
                            'largura' => $package->dimensions->width,
                            'altura' => $package->dimensions->height,
                            'comprimento' => $package->dimensions->length,
                            'peso' => $package->weight
                        ];
                    }
                }

                $response['quotation'][$item->id] = [
                    'Serviço' => $item->name,
                    'Valor' => isset($item->price) ? $item->price : null,
                    'Erro' => isset($item->error) ? $item->error : null,
                    'Entrega' => (isset($item->delivery_range))
                        ? sprintf(
                            '%d a %d dias',
                            $item->delivery_range->min,
                            $item->delivery_range->max
                        )
                        : null,
                    'Pacotes' => $packages
                ];
            }
        }

        return wp_send_json($response, ResponseStatus::HTTP_OK);
    }

    /**
     * Function to return path plugins.
     *
     * @return string
     */
    private function getPluginsPath()
    {
        $dir = dirname(__FILE__);
        $data = explode('/plugin-woocommerce', $dir);
        return $data[0];
    }

    /**
     * Function to get a list of plugins instaleds
     *
     * @return array $plugins
     */
    private function getInstalledPlugins()
    {
        return apply_filters(
            'network_admin_active_plugins',
            get_option('active_plugins')
        );
    }

    /**
     * Function to extract any data
     *
     * @param object $user
     * @return array $data
     */
    private function hideDataMe($data)
    {
        if (empty($data->email)) {
            return [
                'message' => 'Usuário não autenticado'
            ];
        }

        $dataEmail = explode('@', $data->email);

        $total = strlen($dataEmail[0]);
        $hide = round((strlen($dataEmail[0]) / 2));

        return [
            'postal_code' => $data->postal_code,
            'email' => sprintf(
                "%s%s@%s",
                str_repeat("*", $hide),
                substr($dataEmail[0], $hide, $total),
                $dataEmail[1]
            )
        ];
    }

    /**
     * function to get produto to test.
     *
     * @return array
     */
    private function getProductToTest()
    {
        if (!empty($_GET['product'])) {
            $_product = wc_get_product($_GET['product']);
        }

        if (empty($_product)) {
            $products = wc_get_products([]);
            $_product = $products[rand(0, (count($products) - 1))];
        }

        $options = (new Option())->getOptions();

        $quantity = (!empty($_GET['quantity']))
            ? intval($_GET['quantity'])
            : self::DEFAULT_QUANTITY_PRODUCT;

        return [
            "id"              => $_product->get_id(),
            "name"            => $_product->get_name(),
            "quantity"        => $quantity,
            "unitary_value"   => round($_product->get_price(), 2),
            "insurance_value" => (!empty($options->insurance_value))
                ? (round($_product->get_price(), 2) * $quantity)
                : 0,
            "weight"          => DimensionsHelper::convertWeightUnit($_product->weight),
            "width"           => DimensionsHelper::convertUnitDimensionToCentimeter($_product->width),
            "height"          => DimensionsHelper::convertUnitDimensionToCentimeter($_product->height),
            "length"          => DimensionsHelper::convertUnitDimensionToCentimeter($_product->length)
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

    /**
     * function to return shipping methods selected by user.
     *
     * @return array
     */
    private function getShippingMethods()
    {
        $shippingMethods = (new ShippingMelhorEnvioService())
            ->getMethodsActivedsMelhorEnvio();

        $response = [];

        foreach ($shippingMethods as $item) {
            if ($item->enabled == 'yes') {
                $response[$item->code][] = [
                    'title' => $item->method_title,
                    'custom-title' => $item->title,
                    'additional' => [
                        'additional_tax' => $item->instance_settings['additional_tax'],
                        'percent_tax' => $item->instance_settings['percent_tax'],
                        'additional_time' => $item->instance_settings['additional_time'],
                    ]
                ];
            }
        }

        return $response;
    }
}
