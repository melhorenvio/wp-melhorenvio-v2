<?php

namespace Controllers;

use Controllers\PackageController;
use Controllers\UsersController;
use Controllers\ProductsController;
use Controllers\TimeController;
use Controllers\MoneyController;
use Controllers\LogsController;

class CotationController 
{
    const URL = 'https://www.melhorenvio.com.br';

    public function __construct() 
    {
        add_action('woocommerce_checkout_order_processed', array($this, 'makeCotationOrder'));
    }

    /**
     * @param [type] $order_id
     * @return void
     */
    public function makeCotationOrder($order_id) 
    {
        global $woocommerce;

        $to = str_replace('-', '', $woocommerce->customer->get_shipping_postcode());

        $products = (new ProductsController())->getProductsOrder($order_id);

        $result = $this->makeCotationProducts($products, $this->getArrayShippingMethodsMelhorEnvio(), $to);

        (new LogsController)->add(
            $order_id, 
            'Logs cotação', 
            $products, 
            $result, 
            'CotationController', 
            'makeCotationOrder', 
            'https://www.melhorenvio.com.br/api/v2/me/shipment/calculate'
        );

        if (!is_array($result)) {
            $item = $result;
            $result = [];
            $result[] = $item;
        }

        if (!isset($result[0]->id)) {
            return false;
        }

        $result['date_cotation'] = date('Y-m-d H:i:s');

        $chooseMethodSession = $woocommerce->session->get( 'chosen_shipping_methods');

        $chooseMethodSession = end($chooseMethodSession);

        $result['choose_method'] = $this->getCodeShippingSelected($chooseMethodSession);

        add_post_meta($order_id, 'melhorenvio_cotation_v2', $result);
    }

    /**
     * @param [type] $choose
     * @return void
     */
    private function getCodeShippingSelected($choose) 
    {
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

    /**
     * @return void
     */
    public function cotationProductPage() 
    {

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
            "width"  =>  $_POST['data']['produto_largura'],
            "length" =>  $_POST['data']['produto_comprimento'],
            "height" =>  $_POST['data']['produto_altura']
        ];

        $options = [
            'insurance_value' => $_POST['data']['produto_preco']
        ];

        $cotation = $this->makeCotationPackage($package, $this->getArrayShippingMethodsMelhorEnvio(), $_POST['data']['cep_origem'], $options);

        $result = [];

        if (count($cotation) == 1) {
            $result[] = $this->mapObject($cotation);
        } else {
            foreach ($cotation as $item) {

                if (is_null($item->price)) {
                    continue;
                }
                $result[] = $this->mapObject($item);
            }
        }

        echo json_encode([
            'success' => true,
            'data' => $result
        ]);
        die;
    }

    /**
     * @param [type] $item
     * @return void
     */
    private function mapObject($item) 
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'price' => (new MoneyController())->setLabel($item->price),
            'company' => $item->company->name,
            'delivery_time' => (new TimeController)->setLabel($item->delivery_range)
        ];
    }

    /**
     * @param [type] $products
     * @param [type] $services
     * @param [type] $to
     * @return void
     */
    public function makeCotationProducts($products, $services, $to) 
    {
        return $this->makeCotation($to, $services, $products, [], ['']);
    }

    /**
     * @param [type] $package
     * @param [type] $services
     * @param [type] $to
     * @param array $options
     * @return void
     */
    public function makeCotationPackage($package, $services, $to, $options = []) 
    {
        return $this->makeCotation($to, $services, [], $package, $options);
    }

    /**
     * @param [type] $to
     * @param [type] $services
     * @param array $products
     * @param array $package
     * @param [type] $options
     * @return void
     */
    protected function makeCotation($to, $services, $products = [], $package = [], $options)
    {
        if ($token = get_option('wpmelhorenvio_token')) {
            $defaultoptions = [
                "insurance_value" => null,
                "receipt"         => false, 
                "own_hand"        => false, 
                "collect"         => false 
            ];
            
            $opts = array_merge($defaultoptions, $options);

            $from = (new UsersController())->getFrom();

            if (!isset($from->postal_code)) {
                return null;
            }

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

            $response =  json_decode(
                wp_remote_retrieve_body(
                    wp_remote_post(self::URL . '/api/v2/me/shipment/calculate', $params)
                )
            );

            return $response;
        }

        return false;
    }

    /**
     * @param [type] $services
     * @return void
     */
    private function converterArrayToCsv($services) 
    {
        $string = '';

        foreach ($services as $service) {
            $string .= $service . ',';
        }

        return rtrim($string,",");
    }

    /**
     * @return void
     */
    public function getArrayShippingMethodsMelhorEnvio() 
    {
        $methods = [];
        $enableds = $this->getArrayShippingMethodsEnabledByZoneMelhorEnvio();
        $shipping_methods = \WC()->shipping->get_shipping_methods();
        foreach ($shipping_methods as $method) {
            if (!isset($method->code) || is_null($method->code)) {
                continue;
            }

            if (in_array($method->id, $enableds)) {
                $methods[] = $method->code;
            }
        }

        return array_unique($methods);
    }

    /**
     * @return void
     */
    public function getArrayShippingMethodsEnabledByZoneMelhorEnvio() 
    {
        global $wpdb;
        $enableds = [];
        $sql = sprintf('select * from %swoocommerce_shipping_zone_methods where is_enabled = 1', $wpdb->prefix);
        $results = $wpdb->get_results($sql);
        
        foreach ($results as $item){
            $enableds[] = $item->method_id;
        }

        return $enableds;
    }
}

$cotationcontroller = new CotationController();
