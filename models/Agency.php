<?php

namespace Models;

use Models\Address;

class Agency 
{

    const URL = 'https://www.melhorenvio.com.br';

    /**
     * @return void
     */
    public function getAgencies() 
    {
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

        if (!isset($_GET['state']) && !isset($_GET['state']) ) {
            $address = (new Address)->getAddressFrom();
        } else {
            $address = [
                'city' => $_GET['city'],
                'state' => $_GET['state']
            ];
        }

        $response =  json_decode(wp_remote_retrieve_body(wp_remote_request(self::URL . '/api/v2/me/shipment/agencies?company=2&country=BR&state='.$address['state']. '&city='.$address['city'], $params)));
        $agencies = [];
        $agencySelected = get_option('melhorenvio_agency_jadlog_v2');

        foreach($response as $agency) {
            $agencies[] = [
                'id' => $agency->id,
                'name' => $agency->name,
                'company_name' => $agency->company_name,
                'selected' => ($agency->id == intval($agencySelected)) ? true : false,
                'address' => [
                    'address' => $agency->address->address,
                    'city' => $agency->address->city->city,
                    'state' => $agency->address->city->state->state_abbr
                ]
            ];
        }

        return [
            'success' => true,
            'agencies' => $agencies
        ];
    }

    /**
     * @param [type] $id
     * @return void
     */
    public function setAgency($id) 
    {
        delete_option('melhorenvio_agency_jadlog_v2');
        add_option('melhorenvio_agency_jadlog_v2', $id);
        return [
            'success' => true,
            'id' => $id
        ];
    }
}
