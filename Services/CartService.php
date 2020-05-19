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

        $quotation = (new QuotationService())->calculateQuotationByOrderId($order_id);

        $body = array(
            'from' => $from,
            'to' => $to,
            'agency' => (new Agency())->getCodeAgencySelected(),
            'service' => $shipping_method_id,
            'products' => $products,
            'volumes' => $this->getVolumes($quotation, $shipping_method_id), 
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

        $isValid = $this->paramsValid($body);

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

        return (new OrderQuotationService())->addDataQuotation(
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
     * Mount array with volumes by products.
     *
     * @param array $quotation
     * @param int $method_id
     * @return array $volumes
     */
    private function getVolumes($quotation, $method_id)
    {
        $volumes = [];

        foreach ($quotation as $item) {
    
            if ($item->id == $method_id) {

                foreach ($item->packages as $package) {

                    $volumes[] = [
                        'height' => $package->dimensions->height,
                        'width'  => $package->dimensions->width,
                        'length' => $package->dimensions->length,
                        'weight' => $package->weight,
                    ];
                }
            }
        }

        //TODO remover volumes se for correios e tratar o erro.
        if (in_array($method_id, [1,2,13,17])) {
            return $volumes[0];
        }

        return $volumes;
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
    private function paramsValid($body)
    {
        $errors = [];

        if (!array_key_exists("from", $body)) {
            $errors[] = 'Informar origem do envio';
        }

        if (!array_key_exists("to", $body)) {
            $errors[] = 'Informar destino do envio';
        }

        if (!array_key_exists("service", $body)) {
            $errors[] = 'Informar o serviço do envio';
        }

        if (!array_key_exists("products", $body)) {
            $errors[] = 'Informar o produtos do envio';
        }

        if (isset($body['service']) && $body['service'] >= 3 && !array_key_exists("agency", $body)) {
            $errors[] = 'Informar a agência do envio';
        }

        if (!isset($body['volumes']) ) {
            $errors[] = 'Informar os volumess do envio';
        }

        return $errors;
    }   
}
