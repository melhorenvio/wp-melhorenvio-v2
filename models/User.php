<?php

namespace Models;

class User 
{
    const URL = 'https://www.melhorenvio.com.br';

    /**
     * @return void
     */
    public function getBalance() 
    {
        $token = get_option('wpmelhorenvio_token');

        $params = array('headers'=>[
            'Content-Type' => 'application/json',
            'Accept'=>'application/json',
            'Authorization' => 'Bearer '.$token],
        );

        $response = json_decode(wp_remote_retrieve_body(wp_remote_get(self::URL . '/api/v2/me/balance', $params)));
        if (isset($response->balance)) {
            return [
                'success' => true,
                'balance' => 'R$' . number_format($response->balance, 2, ',', '.')
            ];
        }
        
        return [
            'error' => true,
            'message' => 'Erro ao conectar a API'
        ];
    }
}
