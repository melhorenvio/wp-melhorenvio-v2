<?php

namespace Services;

use Models\Address;

/**
 * Class responsible for the service of managing the store salesperson
 */
class SellerService
{
    /**
     * Get data user on API Melhor Envio
     *
     * @return object $dataSeller
     */
    public function getData()
    {
        $data = $this->getDataApiMelhorEnvio();

        $address = (new Address())->getAddressFrom();

        if (!empty($address['address']['id'])) {
            $data->address->address = (!empty($address['address']['address'])) ? $address['address']['address'] : null;
            $data->address->complement = (!empty($address['address']['complement'])) ? $address['address']['complement'] : null;
            $data->address->number = (!empty($address['address']['number'])) ? $address['address']['number'] : null;
            $data->address->district = (!empty($address['address']['district'])) ? $address['address']['district'] : null;
            $data->address->city->city = (!empty($address['address']['city'])) ? $address['address']['city'] : null;
            $data->address->city->state->state_abbr = (!empty($address['address']['state'])) ? $address['address']['state'] : null;
            $data->address->postal_code = (!empty($address['address']['postal_code'])) ? $address['address']['postal_code'] : null;
        }

        return (object) [
            "name" => sprintf("%s %s", $data->firstname, $data->lastname),
            "phone" => (!empty($data->phone->phone)) ? $data->phone->phone : null,
            "email" => $data->email,
            "document" => $data->document,
            "address" => $data->address->address,
            "complement" => $data->address->complement,
            "number" => $data->address->number,
            "district" => $data->address->district,
            "city" => $data->address->city->city,
            "state_abbr" => $data->address->city->state->state_abbr,
            "country_id" => 'BR',
            "postal_code" => $data->address->postal_code
        ];
    }

    /**
     * Get data user on API Melhor Envio
     *
     * @return object $data
     */
    private function getDataApiMelhorEnvio()
    {
        $data = (new RequestService())->request('', 'GET', [], false);

        if (!isset($data->id)) {
            return [
                'success' => false,
                'message' => 'Usuário não encontrado no Melhor Envio'
            ];
        }

        return $data;
    }
}
