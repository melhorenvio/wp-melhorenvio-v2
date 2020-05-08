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

        if (empty($phone) || is_null($phone)) {
            $phone = $order->get_billing_phone();
        }

        return (object) [
            "name" => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            "phone" => (new FormaterHelper())->formatPhone($phone),
            "email" => $order->get_billing_email(),
            //"document" => (!empty($cpf)) ? $cpf : null,
            "document" => "58884939020",
            "company_document" => (!empty($cnpj)) ? $cnpj : null,
            "state_register" => null, // (opcional) (a menos que seja transportadora e logÃ­stica reversa)
            "address" => $order->get_shipping_address_1(),
            "complement" => $order->get_shipping_address_2(),
            "number" => get_post_meta($order_id, '_shipping_number',true),
            "district" =>get_post_meta($order_id, '_shipping_neighborhood',true),
            "city" => $order->get_shipping_city(),
            "state_abbr" => $order->get_shipping_state(),
            "country_id" => 'BR',
            "postal_code" => str_replace('-', '', $order->get_shipping_postcode()),  
        ];
    }
}
