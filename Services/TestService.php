<?php

namespace Services;

class TestService
{
    protected $version;

    public function __construct($version)
    {
        $this->version = $version;
    }

    public function run()
    {   
        $response = [
            'version' => $this->version,
            'token' => substr( get_option('wpmelhorenvio_token'), 0, 50),
            'me' => $this->hideDataMe((new SellerService())->getData()),
            'cep_destiny' => $this->cepDestiny($_GET),
            'packages' => $this->packages($_GET),
            'insurance_value' => $this->insuranceValue($_GET),
            'plugins' => $this->getListPluginsInstaleds(),
            'is_multisite' => is_multisite(),
            'path' => plugin_dir_path( __FILE__ ),
            'ABSPATH' => ABSPATH,
            'path_custom_melhorenvio' => get_option('melhor_envio_path_plugins'),
            'shipping_services' => $this->getShippingServices(),
            'quotation' => (new QuotationService())->calculateQuotationByPackages($this->packages($_GET), $this->cepDestiny($_GET))
        ];

       echo json_encode($response);die;
    }

    /**
     * Function to get cep destiny
     *
     * @param GET $data
     * @return string $cep
     */
    private function cepDestiny($data)
    {
        return (isset($data['cep'])) ? $data['cep'] : '01018020';
    }

    /**
     * Function to get packages
     *
     * @param GET $data
     * @return array $packages
     */
    private function packages($data)
    {
        return [
            'width'  => (isset($data['width']))  ? (float) $data['width']  : 17 ,
            'height' => (isset($data['height'])) ? (float) $data['height'] : 23,
            'length' => (isset($data['length'])) ? (float) $data['length'] : 10,
            'weight' => (isset($data['weight'])) ? (float) $data['weight'] : 1
        ];
    }

    /**
     * Function to get insurance vale
     *
     * @param GET $data
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
        return apply_filters( 'network_admin_active_plugins', get_option( 'active_plugins' ));
    }

    /**
     * Function to get a list of methods shipments
     *
     * @return array $shipping_methods
     */
    private function getShippingServices()
    {
        $services = [];
        foreach ( glob( ABSPATH . '/wp-content/plugins/melhor-envio-cotacao/services_methods/*.php' ) as $filename ) {
            $services[] = $filename;
        }

        foreach ( glob( ABSPATH . '/wp-content/plugins/plugin-woocommerce/services_methods/*.php' ) as $filename ) {
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
}