<?php

namespace Models;

class Agency {

    public function getAgencies() {

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

        $urlApi = 'https://www.melhorenvio.com.br';
        if(WP_ENV !== null && WP_ENV == 'develop') {
            $urlApi = 'https://sandbox.melhorenvio.com.br';
        } 
        $response =  json_decode(wp_remote_retrieve_body(wp_remote_request($urlApi . '/api/v2/me/shipment/agencies?company=2&country=BR', $params)));
        
        $agencies = [];
        $agencySelected = get_option('melhorenvio_agency_jadlog_v2');
        
        foreach($response as $agency) {
            $agencies[] = [
                'id' => $agency->id,
                'name' => $agency->name,
                'company_name' => $agency->company_name,
                'selected' => ($agency->id == $agencySelected) ? true : false
            ];
        }

        return [
            'success' => true,
            'agencies' => $agencies
        ];
    }

    public function setAgency($id) {

        $agency = get_option('melhorenvio_agency_jadlog_v2');
        if (!$agency) {
            add_option('melhorenvio_agency_jadlog_v2', $id);
            return [
                'success' => true,
                'id' => $id
            ];
        }

        update_option('melhorenvio_agency_jadlog_v2', $id);
        return [
            'success' => true,
            'id' => $id
        ];
    }
}