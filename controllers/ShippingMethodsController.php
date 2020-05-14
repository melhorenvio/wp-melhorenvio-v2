<?php

namespace Controllers;

class ShippingMethodsController 
{
    public function getCodes()
    {
        var_dump('deprecado use ShippingService@getCodesEnabled');die;
        /**if (!isset($_SESSION['methods_shipping_api_melhor_envio'])) {
            return $this->updateMethodsShippingCodeSession();
        }
        
        return $_SESSION['methods_shipping_api_melhor_envio']['methods']; */
    }

    /**public function getMethodsShippingCodesViaApi()
    {
        $response = wp_remote_request('https://www.melhorenvio.com.br/api/v2/me/shipment/services');

        if (wp_remote_retrieve_response_code($response) != 200) {
            return [];
        }

        $services =  json_decode(
            wp_remote_retrieve_body(
                $response
            )
        );

        if (empty($services)) {
            return [];
        }

        $servicesIds = [];

        foreach ($services as $service) {
            $servicesIds[] = (string) $service->id;
        }

        return $servicesIds;
    }*/

    /**public function updateMethodsShippingCodeSession()
    {
        if (!isset($_SESSION['methods_shipping_api_melhor_envio'])) {

            $methods = $this->getMethodsShippingCodesViaApi();

            if (empty($methods)) {
                return false;
            }

            return $_SESSION['methods_shipping_api_melhor_envio'] = [
                'methods'    => $methods,
                'updated_at' => date('Y-m-d')
            ];
        }

        $yesterday = date('Y-m-d', strtotime("-1 days"));

        if (date($_SESSION['methods_shipping_api_melhor_envio']['updated_at'] >= $yesterday)) {
            return $_SESSION['methods_shipping_api_melhor_envio'] = [
                'methods'    => $this->getMethodsShippingCodesViaApi(),
                'updated_at' => date('Y-m-d')
            ];
        }

    }*/
}

