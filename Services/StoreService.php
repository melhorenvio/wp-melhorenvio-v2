<?php

namespace Services;

class StoreService
{
    const URL = 'https://api.melhorenvio.com';

    const OPTION_STORES = 'melhorenvio_stores';

    const OPTION_STORE_SELECTED = 'melhorenvio_store_v2';

    const SESSION_STORES = 'melhorenvio_stores';

    const SESSION_STORE_SELECTED = 'melhorenvio_store_v2';

    const ROUTE_MELHOR_ENVIO_COMPANIES = '/companies';

    public $store = null;

    /**
     * Function to get store selected by seller.
     *
     * @return bool|object
     */
    public function getStoreSelected()
    {
        $stores = $this->getStores();

        if (!isset($stores)) {
            return false;
        }

        $store = array_map(function ($store) {
            if (isset($store->selected)) {
                return $store;
            }
        }, $stores);

        if (!isset($store[0]->document)) {
            return false;
        }

        $storeSelected = $store[0];

        $storeSelected->address = $this->getAddressStore($storeSelected);
    
        return $storeSelected;
    }

    /**
     * Function to retrieve stores. 
     *
     * @return bool|array
     */
    public function getStores()
    {
        session_start();

        $codeStore = md5(get_option('home'));

        unset($_SESSION[$codeStore][self::SESSION_STORES]);
        if (isset($_SESSION[$codeStore][self::SESSION_STORES])) {
            return $_SESSION[$codeStore][self::SESSION_STORES];
        }

        $response = (new RequestService())->request(
            self::ROUTE_MELHOR_ENVIO_COMPANIES,
            'GET',
            [],
            false
        );

        $stores = [];

        if (!isset($response->data)) {
            return false;
        }

        $stores = $response->data;

        $storeSelected = $this->getSelectedStoreId();

        $stores = array_map(function ($store) use ($storeSelected) {
            if ($store->id == $storeSelected) {
                $store->selected = true;
            }

            $store->address = $this->getAddressStore($store);

            return $store;
        }, $stores);

        $_SESSION[$codeStore][self::OPTION_STORES] = $stores;

        return $stores;
    }

    /**
     * Function to get the address of a store
     *
     * @param object $store
     * @return object
     */
    public function getAddressStore($store)
    {
        $addresses = (new RequestService())->request(
            self::ROUTE_MELHOR_ENVIO_COMPANIES . '/' . $store->id . '/addresses',
            'GET',
            [],
            false
        );

        if (empty($addresses->data)) {
            return [];
        }

        
        return end($addresses->data);
    }

    /**
     * Return ID of store selected by user
     *
     * @return null|string
     */
    public function getSelectedStoreId()
    {
        return get_option(self::OPTION_STORE_SELECTED, true);
    }

    /**
     * Function to save store selected on wordpress.
     *
     * @param string $idStoreSelected
     * @return bool
     */
    public function setStore($idStoreSelected)
    {
        delete_option(self::OPTION_STORE_SELECTED);
        return add_option(self::OPTION_STORE_SELECTED, $idStoreSelected);
    }
}
