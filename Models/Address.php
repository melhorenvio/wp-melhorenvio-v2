<?php

namespace Models;

use Models\Agency;
use Controllers\TokenController;
use Services\RequestService;

class Address 
{
    const URL = 'https://api.melhorenvio.com';

    const OPTION_ADDRESS = 'melhorenvio_address';

    const OPTION_ADDRESSES = 'melhorenvio_addresses';

    const OPTION_ADDRESS_SELECTED = 'melhorenvio_address_selected_v2';

    const SESSION_ADDRESS_SELECTED = 'melhorenvio_address_selected_v2';

    const ROUTE_MELHOR_ENVIO_ADDRESS = '/addresses';
    
    /**
     *
     * @return void
     */
    public function getAddressesShopping() 
    {
        // Get info on session
        $codeStore = md5(get_option('home'));

        if (isset($_SESSION[$codeStore][self::OPTION_ADDRESS])) {

            return array(
                'success'   => true,
                'origin'    => 'session',
                'addresses' => $_SESSION[$codeStore][self::OPTION_ADDRESS]
            );
        } 

        // Get info on API Melhor Envio
        $response = (new RequestService())->request(
            self::ROUTE_MELHOR_ENVIO_ADDRESS,
            'GET',
            [],
            false
        );
        
        $selectedAddress = get_option(self::OPTION_ADDRESS_SELECTED);

        $_SESSION[$codeStore][self::OPTION_ADDRESS_SELECTED] = get_option(self::OPTION_ADDRESS_SELECTED);

        if (!isset($response->data)) {
            return array(
                'success'   => false,
                'addresses' => null
            );
        }

        $addresses = array();
        foreach ($response->data as $address) {

            $addresses[] = array(
                'id'          => $address->id,
                'address'     => $address->address,
                'complement'  => $address->complement,
                'label'       => $address->label,
                'postal_code' => str_pad($address->postal_code ,8, 0,STR_PAD_LEFT),
                'number'      => $address->number,
                'district'    => $address->district,
                'city'        => $address->city->city,
                'state'       => $address->city->state->state_abbr,
                'country'     => $address->city->state->country->id,
                'selected'    => ($selectedAddress == $address->id) ? true : false
            );
        }

        $_SESSION[$codeStore][self::OPTION_ADDRESS] = $addresses;

        add_option(self::OPTION_ADDRESSES, $address, true);

        return array(
            'success' => true,
            'origin' => 'api',
            'addresses' => $addresses
        );
    }

    public function setAddressShopping($id) 
    {    
        $codeStore = md5(get_option('home'));

        $_SESSION[$codeStore][self::SESSION_ADDRESS_SELECTED] = $id;

        $addressDefault = get_option(self::OPTION_ADDRESS_SELECTED);

        // Clear agencies list in session to load with new address
        unset($_SESSION['melhor_envio']['agencies']);

        if  (!$addressDefault) {
            add_option(self::OPTION_ADDRESS_SELECTED, $id);
            return array(
                'success' => true,
                'id' => $id
            );
        }

        update_option(self::OPTION_ADDRESS_SELECTED, $id);
        return array(
            'success' => true,
            'id' => $id
        );
    }

    /**
     * Return ID of address selected by user
     *
     * @return int
     */
    public function getSelectedAddressId()
    {
        // Find ID on session
        $codeStore = md5(get_option('home'));
        if (isset($_SESSION[$codeStore][self::SESSION_ADDRESS_SELECTED]) && $_SESSION[$codeStore][self::SESSION_ADDRESS_SELECTED]) {
            return $_SESSION[$codeStore][self::SESSION_ADDRESS_SELECTED];
        }

        // Find ID on database wordpress
        $idSelected = get_option(self::OPTION_ADDRESS_SELECTED, true);
        if (!is_bool($idSelected)) {
            return $idSelected;
        }

        return null;
    }

    public function getAddressFrom() 
    {
        $addresses = $this->getAddressesShopping();

        $idAddressSelected = $this->getSelectedAddressId();

        if (is_null($addresses['addresses'])) {
            return null;
        }

        foreach($addresses['addresses'] as $item) {

            if($item['id'] == floatval($idAddressSelected)) {
                return array(
                    'success' => true,
                    'origin'  => 'session/database',
                    'address' => $item
                );
            }
        }

        if (!empty($addresses['addresses'])) {

            return array(
                'success' => true,
                'origin'  => 'database',
                'address' => end($addresses['addresses'])
            );
        }

        return array(
            'success' => false,
            'address' => []
        );
    }

    public function resetData()
    {
        $codeStore = md5(get_option('home'));

        // delete_option(self::OPTION_ADDRESS);

        // delete_option(self::OPTION_ADDRESSES);

        // delete_option(self::OPTION_ADDRESS_SELECTED);

        // unset($_SESSION[$codeStore][self::OPTION_ADDRESS_SELECTED]);

        // unset($_SESSION[$codeStore][self::OPTION_ADDRESS]);
    }
}