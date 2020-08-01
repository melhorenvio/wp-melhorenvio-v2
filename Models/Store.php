<?php

namespace Models;

use Controllers\TokenController;
use Services\RequestService;

class Store
{
    const URL = 'https://api.melhorenvio.com';

    const OPTION_STORES = 'melhorenvio_stores';

    const OPTION_STORE_SELECTED = 'melhorenvio_store_v2';

    const SESSION_STORES = 'melhorenvio_stores';

    const SESSION_STORE_SELECTED = 'melhorenvio_store_v2';

    const ROUTE_MELHOR_ENVIO_COMPANIES = '/companies';

    public $store = null;

    /**
     * @return void
     */
    public function getStories()
    {
        $codeStore = md5(get_option('home'));

        if (isset($_SESSION[$codeStore][self::SESSION_STORES])) {

            return array(
                'success' => true,
                'origin'  => 'session',
                'stores'  => $_SESSION[$codeStore][self::SESSION_STORES]
            );
        }

        $response = (new RequestService())->request(
            self::ROUTE_MELHOR_ENVIO_COMPANIES,
            'GET',
            [],
            false
        );

        $stores = array();

        if (!isset($response->data)) {
            return array(
                'success' => false,
                'stores'  => null
            );
        }

        $storeSelected = $this->getSelectedStoreId();

        foreach ($response->data as $store) {
            $stores[] = array(
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

        $_SESSION[$codeStore][self::OPTION_STORES] = $stores;

        return array(
            'success' => true,
            'origin'  => 'api',
            'stores'  =>  $stores
        );
    }

    /**
     * @param int $id
     * @return void
     */
    public function setStore($id)
    {
        $codeStore = md5(get_option('home'));

        $_SESSION[$codeStore][self::SESSION_STORE_SELECTED] = $id;

        $addressDefault = get_option(self::OPTION_STORE_SELECTED);

        if (!$addressDefault) {

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
        $codeStore = md5(get_option('home'));
        if (isset($_SESSION[$codeStore][self::SESSION_STORE_SELECTED]) && $_SESSION[$codeStore][self::SESSION_STORE_SELECTED]) {
            return $_SESSION[$codeStore][self::SESSION_STORE_SELECTED];
        }

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
        $stores = $this->getStores();

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
}
