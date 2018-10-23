<?php

namespace Controllers;

use Models\Address;
use Models\Store;

class UsersController {

    const URL = 'https://www.melhorenvio.com.br';

    public function __construct(){

    }

    /**
     * @return void
     */
    public function getFrom()
    {
        $info = $this->getInfo();
        if (isset($info->data->message) && preg_match('/unauthenticated/i', $info->data->message) ? false : true) {

            $address = (new Address)->getAddressFrom();

            if (is_null($address)) {
                return false;
            }
            
            $company = (new Store)->getStore();

            return (object) [
                "name" => (isset($company['name'])) ? $company['name']  : $info->data->firstname . ' ' . $info->data->lastname,
                "phone" => $this->mask($info->data->phone->phone, "(##)####-####"),
                "email" => (isset($company['email'])) ? $company['email'] : $info->data->email,
                "document" => $info->data->document,
                "company_document" => (isset($company['document'])) ? $company['document'] : null,
                "state_register" => (isset($company['state_register'])) ? $company['state_register'] : null,
                "address" => $address['address'],
                "complement" => $address['complement'],
                "number" => $address['number'],
                "district" => $address['district'],
                "city" => $address['city'],
                "state_abbr" => $address['state'],
                "country_id" => $address['country'],
                "postal_code" => $address['postal_code']
            ];   
        }

        return false;
    }

    /**
     * @return void
     */
    public function getInfo()
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
                wp_remote_get(self::URL . '/api/v2/me', $params)
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

        return  (object) [
            'success' => true,
            'data' => (object) $dataUser
        ];
    }

    /**
     * @param [type] $order_id
     * @return void
     */
    public function getTo($order_id)
    {    
        $order = new \WC_Order($order_id);

        $cpf  = get_post_meta($order_id, '_billing_cpf', true);
        $cnpj = get_post_meta($order_id, '_billing_cnpj', true);

        return (object) [
            "name" => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            "phone" => $order->get_billing_phone(),
            "email" => $order->get_billing_email(),
            "document" => (!empty($cpf)) ? $cpf : null,
            "company_document" => (!empty($cnpj)) ? $cnpj : null,
            "state_register" => null, // (opcional) (a menos que seja transportadora e logística reversa)
            "address" => $order->get_billing_address_1(),
            "complement" => $order->get_billing_address_2(),
            "number" => get_user_meta($order->user_id, 'billing_number',true),
            "district" => get_user_meta($order->user_id, 'billing_neighborhood',true),
            "city" => $order->get_billing_city(),
            "state_abbr" => $order->get_billing_state(),
            "country_id" => $order->get_billing_country(),
            "postal_code" => str_replace('-', '', $order->get_billing_postcode()),  
        ];

    }

    /**
     * @return void
     */
    public function getBalance()
    {
        $usr = new \Models\User();
        echo json_encode(
            $usr->getBalance()
        );
        die;
    }

    /**
     * @param [type] $val
     * @param [type] $mask
     * @return void
     */
    private function mask($val, $mask)
    {
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
