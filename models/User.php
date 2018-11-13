<?php

namespace Models;

class User 
{
    const URL = 'https://api.melhorenvio.com';

    public function get() 
    {
        $dataUser = get_option('melhorenvio_user_info');

        if (!$dataUser) {
            $token = get_option('wpmelhorenvio_token');
            $params = array('headers'=>[
                'Content-Type' => 'application/json',
                'Accept'=>'application/json',
                'Authorization' => 'Bearer '.$token],
            );

            $response = wp_remote_retrieve_body(
                wp_remote_get(self::URL . '/v2/me', $params)
            );

            if (is_null($response)) {
                return [
                    'error' => true,
                    'message' => 'Erro ao consultar o servidor'
                ];  
            }

            $data = get_object_vars(json_decode($response));
            add_option('melhorenvio_user_info', $data);
            return [
                'success' => true,
                'data' => $data
            ];
        } 

        return (object) $dataUser;
    }

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

        $response = json_decode(wp_remote_retrieve_body(wp_remote_get(self::URL . '/v2/me/balance', $params)));
        if (isset($response->balance)) {
            return [
                'success' => true,
                'balance' => 'R$' . number_format($response->balance, 2, ',', '.'),
                'value' => $response->balance
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Erro ao conectar a API'
        ];
    }
}
