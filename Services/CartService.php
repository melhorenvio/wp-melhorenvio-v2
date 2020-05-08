<?php 

namespace Services;

class CartService
{
    const PLATAFORM = 'WooCommerce V2';

    const ROUTE_MELHOR_ENVIO_ADD_CART = '/cart';

    /**
     * Function to add item on Cart Melhor Envio
     *
     * @param array $products
     * @param array $to
     * @param integer $shipping_method_id
     * @return void
     */
    public function add($products, $to, $shipping_method_id)
    {
        $from = (new SellerService())->getData();

        $body = array(
            'from' => $from,
            'to' => $to,
            'service' => $shipping_method_id,
            'products' => $products,
            'volumes' => $this->getVolumes($products),
            'options' => array(
                "insurance_value" => $this->getInsuranceValueByProducts($products),
                "receipt" => (get_option('melhorenvio_ar') == 'true') ? true : false,
                "own_hand" => (get_option('melhorenvio_mp') == 'true') ? true : false,
                "collect" => false,
                "reverse" => false, 
                "non_commercial" => false, 
                'platform' => self::PLATAFORM,
                'reminder' => null
            )
        );

        $isValid = $this->paramsValid($body);

        if (!empty($isValid)) {

            return [
                'success' => false,
                'message' => $isValid
            ];
        }

        $requestService = new RequestService();

        return $requestService->request(
            self::ROUTE_MELHOR_ENVIO_ADD_CART, 
            'POST', 
            $body
        );

    }

    /**
     * Mount array with volumes by products.
     *
     * @param array $products
     * @return array $volumes
     */
    private function getVolumes($products)
    {
        $volumes = [];

        foreach ($products as $product) {
            $volumes[] = [
                "height" => $product['height'],
                "width" => $product['width'],
                "length" => $product['length'],
                "weight" => $product['weight']
            ];
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
            $errors[] = 'Informar a agÃªncia do envio';
        }

        return $errors;
    }   
}
