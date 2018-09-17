<?php

namespace MelhorEnvio;

class UsersController {

    public function __construct(){

    }

    public function getInfo() {

        $dataUser = get_option('melhorenvio_user_info');
        if (!$dataUser) {
            $token = get_option('melhorenvio_token');

            $params = array('headers'=>[
                'Content-Type' => 'application/json',
                'Accept'=>'application/json',
                'Authorization' => 'Bearer '.$token],
            );

            $response = wp_remote_retrieve_body(wp_remote_get('https://www.melhorenvio.com.br/api/v2/me', $params));

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

        return  (object) [
            'success' => true,
            'data' => (object) $dataUser
        ];

    }
}

