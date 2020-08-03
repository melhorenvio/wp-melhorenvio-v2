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
        $address = (new Address())->getAddressFrom();

        $user = $this->getDataApiMelhorEnvio();

        $store = (new StoreService())->getStoreSelected();

        if (isset($address['address']['id'])) {
            $user->address->address = (isset($address['address']['address'])) ? $address['address']['address'] : null;
            $user->address->complement = (isset($address['address']['complement'])) ? $address['address']['complement'] : null;
            $user->address->number = (isset($address['address']['number'])) ? $address['address']['number'] : null;
            $user->address->district = (isset($address['address']['district'])) ? $address['address']['district'] : null;
            $user->address->city->city = (isset($address['address']['city'])) ? $address['address']['city'] : null;
            $user->address->city->state->state_abbr = (isset($address['address']['state'])) ? $address['address']['state'] : null;
            $user->address->postal_code = (isset($address['address']['postal_code'])) ? $address['address']['postal_code'] : null;
        }

        return (object) [
            "name" => ($store) ? $store->name :  sprintf("%s %s", $user->firstname, $user->lastname),
            "phone" => (isset($user->phone->phone)) ? $user->phone->phone : null,
            "email" => ($store) ? $store->email : $user->email,
            "document" => $user->document,
            "company_document" => ($store) ? $store->document : null,
            "state_register" => ($store) ? $store->state_register : null,
            "address" => $user->address->address,
            "complement" => $user->address->complement,
            "number" => $user->address->number,
            "district" => $user->address->district,
            "city" => $user->address->city->city,
            "state_abbr" => $user->address->city->state->state_abbr,
            "country_id" => 'BR',
            "postal_code" => $user->address->postal_code
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
