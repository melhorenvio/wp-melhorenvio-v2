<?php

namespace Models;

use Controllers\TokenController;

class Store 
{
    const URL = 'https://api.melhorenvio.com';

    /**
     * @return void
     */
    public function getStories() 
    {   
        $codeStore = md5(get_option('home'));

        if (isset($_SESSION[$codeStore]['melhorenvio_stores'])) {
            return [
                'success' => true,
                'session' => true,
                'stores' => $_SESSION[$codeStore]['melhorenvio_stores']
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

        $response =  json_decode(wp_remote_retrieve_body(wp_remote_request(self::URL . '/v2/me/companies', $params)));

        $stories = [];
        $storeSelected = get_option('melhorenvio_store_v2');

        if(!isset($response->data)) {
            return [
                'success' => false,
                'stores' => null
            ];
        }

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

        $_SESSION[$codeStore]['melhorenvio_stores'] = $stories;

        return [
            'success' => true,
            'stores' => $stories
        ];
    }

    /**
     * @param [type] $id
     * @return void
     */
    public function setStore($id) 
    {
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

    /**
     * @return void
     */
    public function getStore() 
    {
        $stores = $this->getStories();
        $store = null;

        if (is_null($stores['stores']) || !isset($stores['stores'])) {
            return null;
        }

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
