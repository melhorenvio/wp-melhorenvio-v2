<?php

namespace Controllers;
use Controllers\PackageController;
use Controllers\UsersController;
use Controllers\ProductsController;
use Controllers\TimeController;

class CotationController {

    public function __construct() {
        //woocommerce_checkout_update_order_review ~> use this action for check when alter shipping method
        //woocommerce_checkout_order_processed ~> use this in prodution
        add_action('woocommerce_checkout_order_processed', array($this, 'makeCotationOrder'));
    }

    public function makeCotationOrder($order_id) {

        global $woocommerce;
        $to = str_replace('-', '', $woocommerce->customer->get_shipping_postcode());

        $productcontroller = new ProductsController();
        $products = $productcontroller->getProductsOrder($order_id);

        $result = $this->makeCotationProducts($products, $this->getArrayShippingMethodsMelhorEnvio(), $to);

        if (!isset($result[0])) {
            return false;
        }

        $result['date_cotation'] = date('Y-m-d H:i:s');

        $chooseMethodSession = $woocommerce->session->get( 'chosen_shipping_methods');
        $chooseMethodSession = end($chooseMethodSession);

        $result['choose_method'] = $this->getCodeShippingSelected($chooseMethodSession);

        add_post_meta($order_id, 'melhorenvio_cotation_v2', $result);
    }

    private function getCodeShippingSelected($choose) {
        $prefix = 0;
        $shipping_methods = \WC()->shipping->get_shipping_methods();
        foreach ($shipping_methods as $method) {
            if (!isset($method->code) || is_null($method->code)) {
                continue;
            }

            if ($choose == 'melhorenvio_' . $method->id) {
                return $method->code;
            }
        }
        return $prefix;
    }

    public function cotationProductPage() {

        if (!isset($_POST['data'])) {
            return [
                'success' => false,
                'message' => 'Dados incompletos'
            ];
        }

        if (!isset($_POST['data']['cep_origem'])) {
            return [
                'success' => false,
                'message' => 'Campo CEP é necessário'
            ];
        }

        $package = [
            "weight" =>  $_POST['data']['produto_peso'],
            "width"  =>  $_POST['data']['produto_comprimento'],
            "height" =>  $_POST['data']['produto_largura'],
            "length" =>  $_POST['data']['produto_altura']
        ];

        $options = [
            'insurance_value' => $_POST['data']['produto_preco']
        ];

        $cotation = $this->makeCotationPackage($package, $this->getArrayShippingMethodsMelhorEnvio(), $_POST['data']['cep_origem'], $options);

        $result = [];

        foreach ($cotation as $item) {
            
            if (is_null($item->price)) {
                continue;
            }

            $result[] = [
                'id' => $item->id,
                'name' => $item->name,
                'price' => 'R$' . number_format($item->price, 2, ',', '.'),
                'company' => $item->company->name,
                'delivery_time' => (new TimeController)->setLabel($item->delivery_range)
            ];
        }

        echo json_encode([
            'success' => true,
            'data' => $result
        ]);
        die;
    }

    public function makeCotationProducts($products, $services, $to) {
        return $this->makeCotation($to, $services, $products, [], ['']);
    }
    
    public function makeCotationPackage($package, $services, $to, $options = []) {
        return $this->makeCotation($to, $services, [], $package, $options);
    }

    protected function makeCotation($to, $services, $products = [], $package = [], $options){
        if ($token = get_option('wpmelhorenvio_token')) {
            $defaultoptions = [
                "insurance_value" => null,
                "receipt"         => false, 
                "own_hand"        => false, 
                "collect"         => false 
            ];
            
            $opts = array_merge($defaultoptions, $options);
    
            $user = new UsersController();
            
            $from = $user->getFrom();
    
            $body = [
                "from" => [
                    "postal_code" => $from->postal_code
                ],
                'to' => [
                    'postal_code' => $to
                ],
                'products' => (!empty($products)) ? $products : null,
                'package' => (!empty($package)) ? $package : null,
                'options' =>  $opts,
                "services" => $this->converterArrayToCsv($services)
            ];
    
            $params = array(
                'headers'           =>  [
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                    'Authorization' => 'Bearer '.$token,
                ],
                'body'  => json_encode($body),
                'timeout'=>10
            );
    
            $response =  json_decode(wp_remote_retrieve_body(wp_remote_post('https://www.melhorenvio.com.br/api/v2/me/shipment/calculate', $params)));
        
            return $response;
        }

        return false;
    }

    private function converterArrayToCsv($services) {
        $string = '';

        foreach ($services as $service) {
            $string .= $service . ',';
        }

        return rtrim($string,",");
    }

    private function normalizeToArray($data) {
        $result = [];
        foreach ($data as $item) {
            $packages = [];
            if (!empty($item->packages)) {
                foreach ($item->packages as $pack) { 
                    $products = [];
                    foreach ($pack->products as $product) {
                        $products[] = [
                            'id' => $product->id,
                            'quantity' => $product->quantity
                        ];
                    }
                    $packages[] = [
                        'price' => $pack->price,
                        'discount' => $pack->discount,
                        'format' => $pack->format,
                        'dimensions' => [
                            'height' => $pack->dimensions->height,
                            'width' => $pack->dimensions->width,
                            'length' => $pack->dimensions->length
                        ],
                        'weight' => $pack->weight,
                        'insurance_value' => $pack->insurance_value,
                        'products' => $products
                    ];
                }
            }
    
            $result[] = [
                'id' => $item->id,
                'name' => $item->name,
                'price' => $item->price,
                'delivery_time' => $item->delivery_time,
                'currency' => $item->currency,
                'delivery_range' => [
                    'min' => $item->delivery_range->min,
                    'max' => $item->delivery_range->max
                ],
                'packages' => $packages,
                'additional_services' => [
                    'receipt' => $item->additional_services->receipt,
                    'own_hand' => $item->additional_services->own_hand,
                    'collect' => $item->additional_services->collect
                ],
                'company' => [
                    'id' => $item->company->id,
                    'name' => $item->company->name
                ],
                'selected' => $item->selected,
            ];
        }
        return $result;
    }

    public function getArrayShippingMethodsMelhorEnvio() {
        $methods = [];
        $shipping_methods = \WC()->shipping->get_shipping_methods();
        foreach ($shipping_methods as $method) {
            if (!isset($method->code) || is_null($method->code)) {
                continue;
            }
            $methods[] = $method->code;
        }
        return array_unique($methods);
    }
}

$cotationcontroller = new CotationController();
