<?php

namespace Services;

use Models\Address;

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

        if (isset($address['address']['id'])) {
            $data->address->address = (isset($address['address']['address'])) ? $address['address']['address'] : null;
            $data->address->complement = (isset($address['address']['complement'])) ? $address['address']['complement'] : null;
            $data->address->number = (isset($address['address']['number'])) ? $address['address']['number'] : null;
            $data->address->district = (isset($address['address']['district'])) ? $address['address']['district'] : null;
            $data->address->city->city = (isset($address['address']['city'])) ? $address['address']['city'] : null;
            $data->address->city->state->state_abbr = (isset($address['address']['state'])) ? $address['address']['state'] : null;
            $data->address->postal_code = (isset($address['address']['postal_code'])) ? $address['address']['postal_code'] : null;
        }

        return (object) [
            "name" => sprintf("%s %s", $data->firstname, $data->lastname),
            "phone" => (isset($data->phone->phone)) ? $data->phone->phone : null,
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
     * @return array $data
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
