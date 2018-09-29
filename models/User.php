<?php

namespace Models;

class User {

    public function getBalance() {

        $token = get_option('melhorenvio_token');
        $params = array('headers'=>[
            'Content-Type' => 'application/json',
            'Accept'=>'application/json',
            'Authorization' => 'Bearer '.$token],
        );

        $urlApi = 'https://www.melhorenvio.com.br';
        if(WP_ENV !== null && WP_ENV == 'develop') {
            $urlApi = 'https://sandbox.melhorenvio.com.br';
        } 
        $response = json_decode(wp_remote_retrieve_body(wp_remote_get($urlApi . '/api/v2/me/balance', $params)));
        if (isset($response->balance)) {
            return [
                'success' => true,
                'balance' => $response->balance
            ];
        }
        
        return [
            'error' => true,
            'message' => 'Erro ao conectar a API'
        ];
    }
}
