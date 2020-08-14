<?php

namespace Services;

use Models\Address;

/**
 * Class responsible for the service of managing the store salesperson
 */
class SellerService
{
    const USER_SESSION = 'user_seller_melhor_envio';
    /**
     * Get data user on API Melhor Envio
     *
     * @return object $dataSeller
     */
    public function getData()
    {
        $data = $this->getDataCached();

        if (!empty($data)) {
            return $data;
        }

        $data = $this->getDataApiMelhorEnvio();

        $address = (new Address())->getAddressFrom();

        $store = (new StoreService())->getStoreSelected();

        if (!empty($address['address']['id'])) {
            $data->address->address = (!empty($address['address']['address'])) ? $address['address']['address'] : null;
            $data->address->complement = (!empty($address['address']['complement'])) ? $address['address']['complement'] : null;
            $data->address->number = (!empty($address['address']['number'])) ? $address['address']['number'] : null;
            $data->address->district = (!empty($address['address']['district'])) ? $address['address']['district'] : null;
            $data->address->city->city = (!empty($address['address']['city'])) ? $address['address']['city'] : null;
            $data->address->city->state->state_abbr = (!empty($address['address']['state'])) ? $address['address']['state'] : null;
            $data->address->postal_code = (!empty($address['address']['postal_code'])) ? $address['address']['postal_code'] : null;
        }

        $data = (object) [
            "name" => (!empty($store->name)) ? $store->name :  sprintf("%s %s", $data->firstname, $data->lastname),
            "phone" => (!empty($data->phone->phone)) ? $data->phone->phone : null,
            "email" => (!empty($store->email)) ? $store->email :  $data->email,
            "document" => (!empty($store->document)) ? null : $data->document,
            'company_document' => (!empty($store->document)) ? $store->document : null,
            "address" => (!empty($store->address->address)) ? $store->address->address : $data->address->address,
            "complement" => (!empty($store->address->complement)) ? $store->address->complement : $data->address->complement,
            "number" => (!empty($store->address->number)) ? $store->address->number : $data->address->number,
            "district" => (!empty($store->address->district)) ? $store->address->district : $data->address->district,
            "city" => (!empty($store->address->city->city)) ? $store->address->city->city : $data->address->city->city,
            "state_abbr" => (!empty($store->address->city->state->state_abbr)) ? $store->address->city->state->state_abbr : $data->address->city->state->state_abbr,
            "country_id" => 'BR',
            "postal_code" => (!empty($store->address->postal_code)) ? $store->address->postal_code : $data->address->postal_code,
        ];

        $this->storeDatSession($data);

        return $data;
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

    /**
     * Function to save data user on session.   
     *
     * @param object $data
     * @return void
     */
    private function storeDatSession($data)
    {
        if (empty($_SESSION)) {
            session_start();
        }

        $_SESSION[self::USER_SESSION]['data'] = $data;
        $_SESSION[self::USER_SESSION]['created'] = date('Y-m-d H:i:s');
    }

    /**
     * Function to get data stored on session.
     *
     * @return object
     */
    private function getDataCached()
    {
        if (empty($_SESSION)) {
            session_start();
        }

        if (isset($_SESSION[self::USER_SESSION]['data']) && !$this->isSessionCachedUserExpired()) {
            return $_SESSION[self::USER_SESSION]['data'];
        }
    }

    /**
     * Function to check if data cacked is expired
     *
     * @return boolean
     */
    private function isSessionCachedUserExpired()
    {
        if (!isset($_SESSION[self::USER_SESSION]['created'])) {
            return true;
        }

        $created = $_SESSION[self::USER_SESSION]['created'];

        $dateLimit = date('Y-m-d H:i:s', strtotime('-1 minutes'));

        if ($dateLimit > $created) {
            unset($_SESSION[self::USER_SESSION]);
            return true;
        }

        return false;
    }
}
