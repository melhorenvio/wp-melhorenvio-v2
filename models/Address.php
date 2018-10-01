<?php

namespace Models;

class Address {

    public function getAddressesShopping() {

        $token = get_option('wpmelhorenvio_token');
        $params = array(
            'headers'           =>  [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ],
            'timeout'=> 10,
            'method' => 'GET'
        );

        $urlApi = 'https://www.melhorenvio.com.br';
        if(WP_ENV !== null && WP_ENV == 'develop') {
            $urlApi = 'https://sandbox.melhorenvio.com.br';
        } 
        $response =  json_decode(wp_remote_retrieve_body(wp_remote_request($urlApi . '/api/v2/me/addresses', $params)));

        $selectedAddress = get_option('melhorenvio_address_selected_v2');

        $addresses = [];
        foreach ($response->data as $address) {
            $addresses[] = [
                'id' => $address->id,
                'label' => $address->label,
                'postal_code' => $address->postal_code,
                'number' => $address->number,
                'disctrict' => $address->district,
                'city' => $address->city->city,
                'state' => $address->city->state->state,
                'selected' => ($selectedAddress == $address->id) ? true : false
            ];
        }

        return [
            'success' => true,
            'addresses' => $addresses
        ];
    }

    public function setAddressShopping($id) {
        
        $addressDefault = get_option('melhorenvio_address_selected_v2');
        if  (empty($addressDefault)) {
            update_option('melhorenvio_address_selected_v2', $id);
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
}