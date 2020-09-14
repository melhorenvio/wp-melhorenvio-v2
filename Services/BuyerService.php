<?php

namespace Services;

use Helpers\FormaterHelper;

class BuyerService
{
    /**
     * Get data of buyer by order id
     *
     * @param int $orderId
     * @return object $data
     */
    public function getDataBuyerByOrderId($orderId)
    {
        $order = new \WC_Order($orderId);

        $cpf  = FormaterHelper::formatDocument(get_post_meta(
            $orderId,
            '_billing_cpf',
            true
        ));
        $cnpj = FormaterHelper::formatDocument(get_post_meta(
            $orderId,
            '_billing_cnpj',
            true
        ));
        $phone = get_post_meta($orderId, '_billing_cellphone', true);

        if (empty($phone)) {
            $phone = $order->get_billing_phone();
        }

        $dataBilling = $this->getBillingAddress($order);
        $dataShipping = $this->getShippingAddress($order);

        $body = (object) [
            "name" => (!empty($order->get_billing_company()))
                ? $order->get_billing_company()
                : $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            "phone" => FormaterHelper::formatPhone($phone),
            "email" => $order->get_billing_email(),
            "state_register" => null,
            "address" => (isset($dataShipping->address))
                ? $dataShipping->address
                : $dataBilling->address,
            "complement" => (isset($dataShipping->complement))
                ? $dataShipping->complement
                : $dataBilling->complement,
            "number" => (isset($dataShipping->number))
                ? $dataShipping->number
                : $dataBilling->number,
            "district" => (isset($dataShipping->district))
                ? $dataShipping->district
                : $dataBilling->district,
            "city" => (isset($dataShipping->city))
                ? $dataShipping->city
                : $dataBilling->city,
            "state_abbr" => (isset($dataShipping->state_abbr))
                ? $dataShipping->state_abbr
                : $dataBilling->state_abbr,
            "country_id" => 'BR',
            "postal_code" => (isset($dataShipping->postal_code))
                ? $dataShipping->postal_code
                : $dataBilling->postal_code,
        ];

        if (empty($cnpj)) {
            $body->document = $cpf;
        }

        if (!empty($cnpj)) {
            $body->company_document = $cnpj;
            unset($body->document);
        }

        return $body;
    }

    /**
     * Get address billing
     *
     * @param post $order
     * @return object $data
     */
    public function getBillingAddress($order)
    {
        $orderId = $order->get_id();

        return (object) [
            "address" => $order->get_billing_address_1(),
            "complement" => $order->get_billing_address_2(),
            "number" => get_post_meta($orderId, '_billing_number', true),
            "district" => get_post_meta($orderId, '_billing_neighborhood', true),
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
        $orderId = $order->get_id();

        return (object) [
            "address" => $order->get_shipping_address_1(),
            "complement" => $order->get_shipping_address_2(),
            "number" => get_post_meta($orderId, '_shipping_number', true),
            "district" => get_post_meta($orderId, '_shipping_neighborhood', true),
            "city" => $order->get_shipping_city(),
            "state_abbr" => $order->get_shipping_state(),
            "country_id" => 'BR',
            "postal_code" => str_replace('-', '', $order->get_shipping_postcode()),
        ];
    }
}
