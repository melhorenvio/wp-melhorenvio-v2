<?php

namespace Models;

use Models\Address;

class Agency 
{
    const URL = 'https://api.melhorenvio.com';

    const AGENCY_SELECTED = 'melhorenvio_agency_jadlog_v2';

    /**
     * @return void
     */
    public function getAgencies() 
    {
        $token = get_option('wpmelhorenvio_token');

        $params = array(
            'headers'           =>  array(
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ),
            'timeout'=> 10,
            'method' => 'GET'
        );

        if (!isset($_GET['state']) && !isset($_GET['state']) ) {
            $address = (new Address)->getAddressFrom();
        } else {
            $address['address'] = array(
                'city'  => $_GET['city'],
                'state' => $_GET['state']
            );
        }

        $response =  json_decode(
            wp_remote_retrieve_body(
                wp_remote_request(self::URL . '/v2/me/shipment/agencies?company=2&country=BR&state='.$address['address']['state']. '&city='.$address['address']['city'], $params)
            )
        );

        $agencies = array();

        $agencySelected = get_option(self::AGENCY_SELECTED);

        foreach( $response as $agency) {

            $agencies[] = array(
                'id'           => $agency->id,
                'name'         => $agency->name,
                'company_name' => $agency->company_name,
                'selected'     => ($agency->id == intval($agencySelected)) ? true : false,
                'address'      => array(
                    'address'  => $agency->address->address,
                    'city'     => $agency->address->city->city,
                    'state'    => $agency->address->city->state->state_abbr
                )
            );
        }

        return array(
            'success'  => true,
            'origin'   => 'api',
            'agencies' => $agencies
        );
    }

    /**
     * @param [type] $id
     * @return void
     */
    public function setAgency($id) 
    {
        delete_option(self::AGENCY_SELECTED);
        add_option(self::AGENCY_SELECTED, $id);
        return array(
            'success' => true,
            'id' => $id
        );
    }
}
