<?php

namespace Services;

use Helpers\FormaterHelper;

class BuyerService
{
    /**
     * Get data of buyer by order_id
     *
     * @param integer $order_id
     * @return array $data
     */
    public function getDataBuyerByOrderId($order_id)
    {
        $order = new \WC_Order($order_id);
    
        $cpf  = get_post_meta($order_id, '_billing_cpf', true);
        $cnpj = get_post_meta($order_id, '_billing_cnpj', true);
        $phone = get_post_meta($order_id, '_billing_cellphone', true);

        $document = ($cpf) ? $cpf : $cnpj; 

        if (empty($phone) || is_null($phone)) {
            $phone = $order->get_billing_phone();
        }

        $dataBilling = $this->getBillingAddress($order);
        $dataShipping = $this->getShippingAddress($order);

        return (object) [
            "name" => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            "phone" => (new FormaterHelper())->formatPhone($phone),
            "email" => $order->get_billing_email(),
            "document" => $document,
            "company_document" => (!empty($cnpj)) ? $cnpj : null,
            "state_register" => null, // (opcional) (a menos que seja transportadora e logÃ­stica reversa)
            "address" => (isset($dataShipping->address)) ? $dataShipping->address : $dataBilling->address, 
            "complement" => (isset($dataShipping->complement)) ? $dataShipping->complement : $dataBilling->complement,
            "number" => (isset($dataShipping->number)) ? $dataShipping->number : $dataBilling->number,
            "district" => (isset($dataShipping->district)) ? $dataShipping->district : $dataBilling->district,
            "city" => (isset($dataShipping->city)) ? $dataShipping->city : $dataBilling->city,
            "state_abbr" => (isset($dataShipping->state_abbr)) ? $dataShipping->state_abbr : $dataBilling->state_abbr,
            "country_id" => 'BR',
            "postal_code" => (isset($dataShipping->postal_code)) ? $dataShipping->postal_code : $dataBilling->postal_code,  
        ];
    }

    /**
     * Get address billing
     *
     * @param post $order
     * @return object $data
     */
    public function getBillingAddress($order)
    {   
        $order_id = $order->get_id();

        return (object) [
            "address" => $order->get_billing_address_1(),
            "complement" => $order->get_billing_address_2(),
            "number" => get_post_meta($order_id, '_billing_number',true),
            "district" => get_post_meta($order_id, '_billing_neighborhood',true),
            "city" => $order->get_billing_city(),
            "state_abbr" => $order->get_billing_state(),
            "country_id" => 'BR',
            "postal_code" => str_replace('-', '', $order->get_billing_postcode()),   
        ];
    }

    /**
     * Get address shipping
     *
     * @param post $order
     * @return object $data
     */
    public function getShippingAddress($order)
    {  
        $order_id = $order->get_id();

        return (object) [
            "address" => $order->get_shipping_address_1(),
            "complement" => $order->get_shipping_address_2(),
            "number" => get_post_meta($order_id, '_shipping_number',true),
            "district" => get_post_meta($order_id, '_shipping_neighborhood',true),
            "city" => $order->get_shipping_city(),
            "state_abbr" => $order->get_shipping_state(),
            "country_id" => 'BR',
            "postal_code" => str_replace('-', '', $order->get_shipping_postcode()),  
        ];
    }
}
