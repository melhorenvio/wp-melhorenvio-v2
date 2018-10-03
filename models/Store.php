<?php

namespace Models;

class Store {

    public function getStories() {

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
        $response =  json_decode(wp_remote_retrieve_body(wp_remote_request($urlApi . '/api/v2/me/companies', $params)));

        $stories = [];
        $storeSelected = get_option('melhorenvio_store_v2');
        
        foreach($response->data as $store) {

            $stories[] = [
                'id' => $store->id,
                'name' => $store->name,
                'company_name' => $store->company_name,
                'document' => $store->document,
                'state_register' => $store->state_register,
                'protocol' => $store->protocol,
                'email' => $store->email,
                'website' => $store->website,
                'selected' => ($store->id == $storeSelected) ? true : false
            ];
        }

        return [
            'success' => true,
            'stores' => $stories
        ];
    }

    public function setStore($id) {
        $addressDefault = get_option('melhorenvio_store_v2');

        if  (!$addressDefault) {
            add_option('melhorenvio_store_v2', $id);
            return [
                'success' => true,
                'id' => $id
            ];
        }

        update_option('melhorenvio_store_v2', $id);
        return [
            'success' => true,
            'id' => $id
        ];
    }

    public function getStore() {
        $stores = $this->getStories();
        $store = null;
        foreach ($stores['stores'] as $item) {
            if ($item['selected']) {
                $store = $item;
            }
        }

        if ($store == null && !empty($store['stores'])) {
            return end($store['stores']);
        }

        return $store;
    }

}