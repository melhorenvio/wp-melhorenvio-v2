<?php

namespace Controllers;

use Services\LocationService;

class LocationsController 
{
    /**
     * Function to search for address in zip code api
     *
     * @param string $postalCode
     * @return json
     */
    public function getAddressByPostalCode($postalCode)
    {
        $address =  (new LocationService())->getAddressByPostalCode($postalCode);

        if (is_null($address)) {
            return wp_send_json([
                'message' => sprintf("Não encontramos endereço para o CEP %s", $postalCode)
            ], 404);
        }

        return wp_send_json([
            $address
        ], 200);
    }
}
