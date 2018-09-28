<?php

namespace Models;

class Store {

    public function getStories() {

        $token = get_option('melhorenvio_token');
        $params = array(
            'headers'           =>  [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ],
            'timeout'=> 10,
            'method' => 'GET'
        );

        $response =  json_decode(wp_remote_retrieve_body(wp_remote_request('https://www.melhorenvio.com.br/api/v2/me/companies', $params)));

        $stories = [];
        $storeSelected = get_option('melhorenvio_store_v2');
        
        foreach($response->data as $store) {
            $stories[] = [
                'id' => $store->id,
                'name' => $store->name,
                'company_name' => $store->company_name,
                'selected' => ($store->id == $storeSelected) ? true : false
            ];
        }

        return [
            'success' => true,
            'stories' => $stories
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

}