<?php

namespace Controllers;

use Services\ShippingMelhorEnvioService;

class ShippingMethodsController
{
    /**
     * function to search for the shipping services available in session.
     *
     * @return array
     */
    public function getCodes()
    {
        if (!isset($_SESSION['methods_shipping_api_melhor_envio']['methods'])) {
            return [];
        }

        return $_SESSION['methods_shipping_api_melhor_envio']['methods'];
    }

    /**
     * function to search for the shipping services ID available in the Melhor Envio api
     *
     * @return array
     */
    public function getMethodsShippingCodesMelhorEnvio()
    {
        $services = (new ShippingMelhorEnvioService())->getServicesApiMelhorEnvio();

        if (empty($services)) {
            return [];
        }

        return array_map(function ($service) {
            return (string) $service->id;
        }, $services);
    }

    /**
     * Function to update available "Melhor ENvio" services in the session
     *
     * @return array
     */
    public function updateMethodsShippingCodeSession()
    {
        if (!isset($_SESSION['methods_shipping_api_melhor_envio'])) {
            $methods = $this->getMethodsShippingCodesMelhorEnvio();

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
                'methods'    => $this->getMethodsShippingCodesMelhorEnvio(),
                'updated_at' => date('Y-m-d')
            ];
        }
    }
}
