<?php

namespace Controllers;

use Controllers\PackageController;
use Controllers\UsersController;
use Controllers\ProductsController;
use Controllers\TimeController;
use Controllers\MoneyController;
use Controllers\LogsController;
use Controllers\OrdersController;
use Models\Order;

class CotationController 
{
    const URL = 'https://api.melhorenvio.com';

    public function __construct() 
    {
        // woocommerce_before_checkout_form
        // woocommerce_checkout_order_processed
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
            'https://api.melhorenvio.com/v2/me/shipment/calculate'
        );

        if (!is_array($result)) {
            $item = $result;
            $result = [];
            $result[] = $item;
        }

        if (!isset($result[0]->id)) {
            return false;
        }

        // Remove a cotação que não esta disponivel 
        foreach ($result as $key => $item) {
            if (!isset($item->price)) {
                unset($result[$key]);
            }
        }   

        $result['date_cotation'] = date('Y-m-d H:i:s');
        $result['choose_method'] = $this->getMethodId($order_id);

        add_post_meta($order_id, 'melhorenvio_cotation_v2', $result);
    }

    public function getMethodId($order_id)
    {
        global $wpdb;
        $sql = sprintf('
            select 
                meta_value as method 
            from 
                %swoocommerce_order_itemmeta 
            where 
                meta_key = "method_id" and 
                order_item_id IN (
                    select 
                        order_item_id 
                    from 
                        %swoocommerce_order_items where order_id = %d and 
                        order_item_type = "shipping"
                    ) ', $wpdb->prefix, $wpdb->prefix, $order_id);

        $result = $wpdb->get_results($sql);
        $result = end($result);
        return $this->getCodeMelhorEnvioShippingMethod($result->method);
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

    public function refreshCotation()
    {
        $order_id = $_GET['id'];
        $this->makeCotationOrder($order_id);
        
        $order = (new OrdersController())->get($order_id);

        if (!$order) {
            return null;
        }

        echo json_encode($order);
        die;
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

        $products[] = [
            'id' => $_POST['data']['id_produto'],
            "weight" =>  floatval($_POST['data']['produto_peso']),
            "width"  =>  floatval($_POST['data']['produto_largura']),
            "length" =>  floatval($_POST['data']['produto_comprimento']),
            "height" =>  floatval($_POST['data']['produto_altura']),
            'quantity' => intval($_POST['data']['quantity']),
            'insurance_value' => floatval($_POST['data']['produto_preco'])
        ];

        $options = [];

        $cotation = $this->makeCotationProducts($products, $this->getArrayShippingMethodsMelhorEnvio(), $_POST['data']['cep_origem'], $options);

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
            'price' => (new MoneyController())->setLabel($item->price, $item->id),
            'company' => $item->company->name,
            'delivery_time' => (new TimeController)->setLabel($item->delivery_range, $item->id)
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

            if (!empty($products)) {
                unset($opts['insurance_value']);
            }

            $body = [
                "from" => [
                    "postal_code" => $from->postal_code
                ],
                'to' => [
                    'postal_code' => $to
                ],
                'options' =>  $opts,
                "services" => $this->converterArrayToCsv($services)
            ];

            if (!empty($products)) {
                $body['products'] = $products;
            }

            if (!empty($package)) {
                $body['package'] = $package;
            }

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
                    wp_remote_post(self::URL . '/v2/me/shipment/calculate', $params)
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
    public function getCodeMelhorEnvioShippingMethod($method_id) 
    {
        $method_id =  str_replace('melhorenvio_', '', $method_id);
        $shipping_methods = \WC()->shipping->get_shipping_methods();
        foreach ($shipping_methods as $method) {
            
            if($method_id == $method->id) {
                return $method->code;
            }
        }
        //TODO Rever caso nao tenha cotacao selecionada
        return 3;
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
