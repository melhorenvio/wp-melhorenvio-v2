<?php

namespace Controllers;

use Models\Address;
use Models\Store;
use Models\User;

class UsersController {

    const URL = 'https://api.melhorenvio.com';

    /**
     * @return void
     */
    public function getInfo()
    {
        $user = (new User())->get();
        return (object) [
            'success' => true,
            'data' => $user
        ];
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
            "address" => $order->get_shipping_address_1(),
            "complement" => $order->get_shipping_address_2(),
            "number" => get_post_meta($order_id, '_shipping_number',true),
            "district" =>get_post_meta($order_id, '_shipping_neighborhood',true),
            "city" => $order->get_shipping_city(),
            "state_abbr" => $order->get_shipping_state(),
            "country_id" => $order->get_shipping_country(),
            "postal_code" => str_replace('-', '', $order->get_shipping_postcode()),  
        ];
    }

    /**
     * @return void
     */
    public function getBalance()
    {
        $balance = (new User())->getBalance();
        echo json_encode($balance);
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
