<?php

namespace Models;

use Models\Agency;
use Controllers\TokenController;

class Address 
{
    const URL = 'https://api.melhorenvio.com';
    
    /**
     *
     * @return void
     */
    public function getAddressesShopping() 
    {
        $codeStore = md5(get_option('home'));

        if (isset($_SESSION[$codeStore]['melhorenvio_address'])) {

            return [
                'success'   => true,
                'session'   => true,
                'addresses' => $_SESSION[$codeStore]['melhorenvio_address']
            ];
        } 

        $token = (new TokenController())->token();

        $params = array(
            'headers'           =>  [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ],
            'timeout'=> 10,
            'method' => 'GET'
        );
        
        $response =  json_decode(wp_remote_retrieve_body(wp_remote_request(self::URL . '/v2/me/addresses', $params)));
        
        $selectedAddress = get_option('melhorenvio_address_selected_v2');

        $_SESSION[$codeStore]['melhorenvio_address_selected_v2'] = get_option('melhorenvio_address_selected_v2');

        if (!isset($response->data)) {
            return [
                'success' => false,
                'addresses' => null
            ];
        }

        $addresses = [];
        foreach ($response->data as $address) {

            $addresses[] = [
                'id' => $address->id,
                'address' => $address->address,
                'complement' => $address->complement,
                'label' => $address->label,
                'postal_code' => str_pad($address->postal_code ,8, 0,STR_PAD_LEFT),
                'number' => $address->number,
                'district' => $address->district,
                'city' => $address->city->city,
                'state' => $address->city->state->state_abbr,
                'country' => $address->city->state->country->id,
                'selected' => ($selectedAddress == $address->id) ? true : false
            ];
        }

        $_SESSION[$codeStore]['melhorenvio_address'] = $addresses;

        return [
            'success' => true,
            'addresses' => $addresses
        ];
    }

    public function setAddressShopping($id) 
    {    
        $addressDefault = get_option('melhorenvio_address_selected_v2');
        if  (empty($addressDefault)) {
            add_option('melhorenvio_address_selected_v2', $id);
            return [
                'success' => true,
                'id' => $id
            ];
        }

        update_option('melhorenvio_address_selected_v2', $id);
        return [
            'success' => true,
            'id' => $id
        ];
    }

    public function getAddressFrom() 
    {
        $addresses = $this->getAddressesShopping();

        if (is_null($addresses['addresses'])) {
            return null;
        }

        $address = null;
        foreach($addresses['addresses'] as $item) {
            if($item['selected']) {
                $address = $item;
            }
        }

        if ($address == null && !empty($addresses['addresses'])) {
            return end($addresses['addresses']);
        }

        return $address;
    }
}