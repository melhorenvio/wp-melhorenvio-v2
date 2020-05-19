<?php

namespace Services;

use Models\User;
use Models\Address;

class SellerService
{
    /**
     * Get data user on API Melhor Envio
     *
     * @return array $dataSeller
     */
    public function getData()
    {
        $data = $this->getDataApiMelhorEnvio();   

        $address = (new Address())->getAddressFrom();

        if(isset($address['address']['id'])) {

            $data->address->address = $address['address']['address'];
            $data->address->complement = $address['address']['complement'];
            $data->address->number = $address['address']['number'];
            $data->address->district = $address['address']['district'];
            $data->address->city->city = $address['address']['city'];
            $data->address->city->state->state_abbr = $address['address']['state'];
            $data->address->postal_code = $address['address']['postal_code'];
        }

        return (object) [
            "name" => sprintf("%s %s", $data->firstname, $data->lastname),
            "phone" => (isset($data->phone->phone)) ? $data->phone->phone : null,
            "email" => $data->email,
            "document" => $data->document,
            //"company_document" => null,
            //"state_register" => null,
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
