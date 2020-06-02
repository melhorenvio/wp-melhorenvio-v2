<?php 

namespace Services;

use Models\Agency;

class CartService
{
    const PLATAFORM = 'WooCommerce V2';

    const ROUTE_MELHOR_ENVIO_ADD_CART = '/cart';

    /**
     * Function to add item on Cart Melhor Envio
     *
     * @param int $order_id
     * @param array $products
     * @param array $to
     * @param integer $shipping_method_id
     * @return void
     */
    public function add($order_id, $products, $to, $shipping_method_id)
    {
        $from = (new SellerService())->getData();

        //$quotation = (new QuotationService())->calculateQuotationByOrderId($order_id);

        $body = array(
            'from' => $from,
            'to' => $to,
            'agency' => (new Agency())->getCodeAgencySelected(),
            'service' => $shipping_method_id,
            //'products' => $products,
            //'volumes' => $this->getVolumes($quotation, $shipping_method_id), 
            'options' => array(
                "insurance_value" => $this->getInsuranceValueByProducts($products),
                "receipt" => (get_option('melhorenvio_ar') == 'true') ? true : false,
                "own_hand" => (get_option('melhorenvio_mp') == 'true') ? true : false,
                "collect" => false,
                "reverse" => false, 
                "non_commercial" => true, 
                'platform' => self::PLATAFORM,
                'reminder' => null
            )
        );

        $isValid = $this->paramsValid($body, $order_id);

        if (!empty($isValid)) {

            return [
                'success' => false,
                'errors' => $isValid
            ];
        }

        $result = (new RequestService())->request(
            self::ROUTE_MELHOR_ENVIO_ADD_CART, 
            'POST', 
            $body,
            true
        );      

        if ( array_key_exists('errors', $result) ) {
            return $result;
        }

        return (new OrderQuotationService())->updateDataQuotation(
            $order_id, 
            $result->id, 
            $result->protocol, 
            'pending', 
            $shipping_method_id,
            null
        );
    }

    public function remove($order_id)
    {
        $data = (new OrderQuotationService())->getData($order_id);

        if (!isset($data['order_id'])) {
            return [
                'success' => false,
                'errors' => 'Pedido não encontrado.'
            ];
        }

        (new OrderQuotationService())->removeDataQuotation($order_id);

        return (new RequestService())->request(
            self::ROUTE_MELHOR_ENVIO_ADD_CART . '/' . $data['order_id'], 
            'DELETE', 
            []
        );
    }

    /**
     * Sum values of products.
     *
     * @param array $products
     * @return float $value
     */
    private function getInsuranceValueByProducts($products)
    {
        $value = 0;

        foreach ($products as $product) {
            $value += ($product['unitary_value'] * $product['quantity']);
        }

        return $value;
    }

    /**
     * Function to validate params before send to request
     *
     * @param array $body
     * @return void
     */
    private function paramsValid($body, $order_id)
    {
        $errors = [];

        if (!array_key_exists("from", $body)) {
            $errors[] = sprintf("Informar origem do envio do pedido %s", $order_id);
        }

        if (!array_key_exists("to", $body)) {
            $errors[] = sprintf("Informar destino do envio do pedido %s", $order_id);
        }

        if (!array_key_exists("service", $body)) {
            $errors[] = sprintf("Informar o serviço do envio do pedido %s", $order_id);
        }

        if (!array_key_exists("products", $body)) {
            $errors[] = sprintf("Informar o produtos do envio do pedido %s", $order_id);
        }

        if (isset($body['service']) && $body['service'] >= 3 && !array_key_exists("agency", $body)) {
            $errors[] = sprintf("Informar a agência do envio do pedido %s", $order_id);
        }

        if (!isset($body['volumes']) ) {
            $errors[] = sprintf("Informar os volumes do envio do pedido %s", $order_id);
        }

        return $errors;
    }   
}
