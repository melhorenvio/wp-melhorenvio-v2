<?php

namespace Models;

class ShippingService
{
    const SERVICES_CORREIOS = [1, 2, 17];

    const SERVICES_JADLOG = [3, 4];

    const SERVICES_AZUL = [15, 16];

    const SERVICES_VIA_BRASIL = [9];

    const OPTIONS_SHIPPING_SERVICES = 'shipping_services_melhor_envio';

    /**
     * Function to return avalaible services.
     *
     * @return array
     */
    public static function getAvailableServices()
    {
        return array_merge(
            self::SERVICES_CORREIOS,
            self::SERVICES_JADLOG,
            self::SERVICES_AZUL,
            self::SERVICES_VIA_BRASIL
        );
    }

    /**
     * function to save shipping services.
     *
     * @param array $shippingServices
     * @return int
     */
    public function save($shippingServices)
    {
        delete_option(self::OPTIONS_SHIPPING_SERVICES);
        return add_option(self::OPTIONS_SHIPPING_SERVICES, $shippingServices, '', true);
    }

    /**
     * function to get shipping services.
     *
     * @return array
     */
    public function get()
    {
        return get_option(self::OPTIONS_SHIPPING_SERVICES);
    }

    /**
     * function to delete shipping services.
     *
     * @return bool
     */
    public function destroy()
    {
        return delete_option(self::OPTIONS_SHIPPING_SERVICES);
    }
}
