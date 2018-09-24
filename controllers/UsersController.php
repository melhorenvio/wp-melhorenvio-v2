<?php

namespace Controllers;

class UsersController {

    public function __construct(){

    }

    public function getFrom() {

        $info = $this->getInfo();
        return (object) [
            "name" => $info->data->firstname . ' ' . $info->data->lastname,
            "phone" => $this->mask($info->data->phone->phone, "(##)####-####"),
            "email" => $info->data->email,
            "document" => $info->data->document,
            "company_document" => null, // TODO
            "state_register" => null, // TODO
            "address" => $info->data->address->address,
            "complement" => $info->data->address->complement,
            "number" => $info->data->address->number,
            "district" => $info->data->address->district,
            "city" => $info->data->address->city->city,
            "state_abbr" => $info->data->address->city->state->state_abbr,
            "country_id" => $info->data->address->city->state->country->id,
            "postal_code" => $info->data->address->postal_code
        ];
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

    private function mask($val, $mask){
        $maskared = '';
        $k = 0;
        for($i = 0; $i<=strlen($mask)-1; $i++) {
            if($mask[$i] == '#') {
                if(isset($val[$k]))
                    $maskared .= $val[$k++];
                }
                else
                {
                if(isset($mask[$i]))
                $maskared .= $mask[$i];
            }
        }
        return $maskared;
    }
}

