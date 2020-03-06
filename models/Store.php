<?php

namespace Models;

use Controllers\TokenController;

class Store 
{
    const URL = 'https://api.melhorenvio.com';

    const OPTION_STORES = 'melhorenvio_stores';

    const OPTION_STORE_SELECTED = 'melhorenvio_store_v2';

    const SESSION_STORES = 'melhorenvio_stores';

    const SESSION_STORE_SELECTED = 'melhorenvio_store_v2';

    public $store = null;

    /**
     * @return void
     */
    public function getStories() 
    {   
        // Get data on session
        $codeStore = md5(get_option('home'));

        //$idStoreSelected = $this->getSelectedStoreId();
        
        // Get stores in session
        if (isset($_SESSION[$codeStore][self::SESSION_STORES])) {

            return array(
                'success' => true,
                'origin'  => 'session',
                'stores'  => $_SESSION[$codeStore][self::SESSION_STORES]
            );
        }
        // Get data on database wordpress
        // $stores = get_option(self::OPTION_STORES, true);
        /*
        if (!is_bool($stores)) {

            foreach ($stores as $key => $store) {
                if ($store['id'] == $idStoreSelected) {
                    $stores[$key]['selected'] = true;
                } else {
                    $stores[$key]['selected'] = false;
                }
            }

            $_SESSION[$codeStore][self::SESSION_STORES] = $stores;

            return array(
                'success' => true,
                'origin'  => 'database',
                'stores'  => $stores 
            );
        }
        */
        // Get data on API Melhor Envio
        $token = (new TokenController())->token();

        $params = array(
            'headers'           =>  array(
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ),
            'timeout'=> 10,
            'method' => 'GET'
        );

        $response =  json_decode(
            wp_remote_retrieve_body(
                wp_remote_request(self::URL . '/v2/me/companies', $params)
            )
        );

        $stories = array();

        if(!isset($response->data)) {
            return array(
                'success' => false,
                'stores'  => null
            );
        }

        $storeSelected = $this->getSelectedStoreId();

        foreach($response->data as $store) {
            $stories[] = array(
                'id'             => $store->id,
                'name'           => $store->name,
                'company_name'   => $store->company_name,
                'document'       => $store->document,
                'state_register' => $store->state_register,
                'protocol'       => $store->protocol,
                'email'          => $store->email,
                'website'        => $store->website,
                'selected'       => ($store->id == floatval($storeSelected)) ? true : false
            );
        }

        $_SESSION[$codeStore][self::OPTION_STORES] = $stories;

        // add_option(self::OPTION_STORES, $stories, true);

        return array(
            'success' => true,
            'origin'  => 'api',
            'stores'  =>  $stories
        );
    }

    /**
     * @param [type] $id
     * @return void
     */
    public function setStore($id) 
    {
        $codeStore = md5(get_option('home'));

        $_SESSION[$codeStore][self::SESSION_STORE_SELECTED] = $id;        

        $addressDefault = get_option(self::OPTION_STORE_SELECTED);

        if  (!$addressDefault) {

            add_option(self::OPTION_STORE_SELECTED, $id);
            return array(
                'success' => true,
                'id' => $id
            );
        }

        update_option(self::OPTION_STORE_SELECTED, $id);

        return array(
            'success' => true,
            'id' => $id
        );
    }

    /**
     * Return ID of store selected by user
     *
     * @return int
     */
    public function getSelectedStoreId()
    {
        // Find ID on session
        $codeStore = md5(get_option('home'));
        if (isset($_SESSION[$codeStore][self::SESSION_STORE_SELECTED]) && $_SESSION[$codeStore][self::SESSION_STORE_SELECTED]) {
            return $_SESSION[$codeStore][self::SESSION_STORE_SELECTED];
        }

        // Find ID on database wordpress
        $idSelected = get_option(self::OPTION_STORE_SELECTED, true);
        if (!is_bool($idSelected)) {
            return $idSelected;
        }

        return null;
    }

    /**
     * @return Object Store
     */
    public function getStore() 
    {
        $stores = $this->getStories();

        if (is_null($stores['stores']) || !isset($stores['stores'])) {
            return null;
        }

        $idSelected = $this->getSelectedStoreId();

        foreach ($stores['stores'] as $item) {
            if ($item['id'] == $idSelected) {
                return $item;
            }
        }

        if (is_array($stores)) {
            return end($stores['stores']);
        }

        return null;
    }

    /**
     * Reset data of stores
     *
     * @return void
     */
    public function resetData()
    {
        $codeStore = md5(get_option('home'));

        // unset($_SESSION[$codeStore][self::SESSION_STORES]);

        // unset($_SESSION[$codeStore][self::SESSION_STORE_SELECTED]);

        // delete_option(self::OPTION_STORES);

        // delete_option(self::OPTION_STORE_SELECTED);
    }
}
