<?php

namespace Controllers;

use Controllers\PackageController;
use Controllers\UsersController;
use Controllers\ProductsController;
use Controllers\TimeController;
use Controllers\MoneyController;
use Controllers\LogsController;
use Controllers\OrdersController;
use Controllers\Optionscontroller;
use Models\Order;

class CotationController 
{
    const URL = 'https://q-engine-hub.melhorenvio.com';

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
        if (!$to) {
            $order = new \WC_Order($order_id);
            $to = str_replace('-', '', $order->get_shipping_postcode());
        }
    
        $products = (new ProductsController())->getProductsOrder($order_id);

        $result = $this->makeCotationProducts($products, $this->getArrayShippingMethodsMelhorEnvio(), $to, [], 'cache');

        (new LogsController)->add(
            $order_id, 
            'Logs cotação', 
            $products, 
            $result, 
            'CotationController', 
            'makeCotationOrder', 
            'https://api.melhorenvio.com/v2/me/shipment/calculate'
        );

        // Remove a cotação que não esta disponivel 
        foreach ($result as $key => $item) {
            if (!isset($item->price)) {
                unset($result[$key]);
            }
        }   

        $totalCart = 0;
        $freeShiping = false;
        foreach(WC()->cart->cart_contents as $cart) {
            $totalCart += $cart['line_subtotal'];
        }

        foreach(WC()->cart->get_coupons() as $cp) {
            if ($cp->discount_type == 'fixed_cart' && $totalCart >= $cp->amount ) {
                $freeShiping = true;
            }
        }

        $result['date_cotation'] = date('Y-m-d H:i:s');
        $result['choose_method'] = $this->getMethodId($order_id);
        $result['free_shipping'] = $freeShiping;

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

        $package = [
            'destination' => [
                'country' => 'BR',
                'state' => 'RS',
                'postcode' => $_POST['data']['cep_origem'] 
            ],
            'cotationProduct' => [
                [
                    'id' => $_POST['data']['id_produto'],
                    "weight" =>  floatval($_POST['data']['produto_peso']),
                    "width"  =>  floatval($_POST['data']['produto_largura']),
                    "length" =>  floatval($_POST['data']['produto_comprimento']),
                    "height" =>  floatval($_POST['data']['produto_altura']),
                    'quantity' => intval($_POST['data']['quantity']),
                    'insurance_value' => floatval($_POST['data']['produto_preco'])
                ]
            ]
        ];

        $shipping_zone = \WC_Shipping_Zones::get_zone_matching_package( $package );
        
        $shipping_methods = $shipping_zone->get_shipping_methods( true );

        $rates = [];
        
        foreach($shipping_methods as $shipping_method) {

            $rate = $shipping_method->get_rates_for_package( $package );

            if (empty($rate)) {
                continue;
            }

            $rates[] = $this->mapObject($rate[key($rate)]);
        }   

        echo json_encode([
            'success' => true,
            'data' => $rates
        ]);
        die;
    }

    /**
     * @param [type] $item
     * @return void
     */
    private function mapObject($item) 
    {

        $name = null;
        if (isset($item->meta_data['name'])) {
            $name = $item->meta_data['name'];
        }

        $company = null;
        if (isset($item->meta_data['company'])) {
            $company = $item->meta_data['company'];
        }

        $delivery = null;
        if (isset($item->meta_data['delivery_time']->min)) {

            $delivery->min = $item->meta_data['delivery_time']->min;
            $delivery->max = $item->meta_data['delivery_time']->max;
        }

        $method = (new optionsController())->getName($item->get_id(),$name, $company, $item->get_label());

        return [
            'id' => $item->get_id(),
            'name' => $method['method'],
            'price' => (new MoneyController())->setLabel($item->get_cost(), $item->get_id()),
            'company' => $method['company'],
            'delivery_time' => (new TimeController)->setLabel($item->meta_data['delivery_time'], $item->get_id())
        ];
    }

    /**
     * @param [type] $products
     * @param [type] $services
     * @param [type] $to
     * @return void
     */
    public function makeCotationProducts($products, $services, $to, $options, $all) 
    {   
        return $this->makeCotation($to, $services, $products, [], $options, $all);
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
        return $this->makeCotation($to, $services, [], $package, $options, false);
    }

    /**
     * @param [type] $to
     * @param [type] $services
     * @param array $products
     * @param array $package
     * @param [type] $options
     * @return void
     */
    protected function makeCotation($to, $services, $products = [], $package = [], $options = [], $all = false)
    {
        $token = (new TokenController())->token();

        if ($token) {

            $options = [
                "insurance_value" => null,
                "receipt"         => false, 
                "own_hand"        => false, 
                "collect"         => false 
            ];

            $from = (new UsersController())->getFrom();

            if (!isset($from->postal_code)) {
                return null;
            }

            $body = [
                'from' => [
                    'postal_code' => $from->postal_code
                ],
                'to' => [
                    'postal_code' => $to
                ],
                'options' => [
                    'own_hand' => false,
                    'receipt'  => false
                ],
                'settings' => [
                    'show' => [
                        'price' => true,
                        'discount' => true,
                        'delivery' => true
                    ]
                ]
            ];

            if (!empty($products)) {
                foreach ($products as $key => $product) {
                    $body['products'][$key] = [
                        'id' => $product['id'],
                        'quantity' => $product['quantity'],
                        'insurance' => $product['insurance_value'],
                    ];

                    $body['products'][$key]['volumes'][] = [
                        'height' => $product['height'],
                        'width'  => $product['width'],
                        'length' => $product['length'],
                        'weight' => $product['weight']
                    ];
                }
            }

            if (!empty($package)) {
                $body['volumes'][] = $package;
            }

            $params = array(
                'headers'           =>  [
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                    'Authorization' => 'Bearer '.$token,
                ],
                'body'   => json_encode($body),
                'timeout'=> 10
            );

            $hashCotation = md5(json_encode($body));

            $codeStore = md5(get_option('home'));


            if (!isset($_SESSION[$codeStore]['cotations'][$hashCotation]['results'])) {

                $response = json_decode(
                    wp_remote_retrieve_body(
                        wp_remote_post(self::URL . '/api/v1/calculate', $params)
                    )
                );

                (new LogsController)->addResponse($response, $body, $to);

                $filterCotations = [];
                foreach ($response as $item) {
                    $filterCotations[$item->id] = $item;
                }
                
                $response = $filterCotations;

                $_SESSION[$codeStore]['cotations'][$hashCotation]['created'] = date('Y-m-d h:i:s');
                $_SESSION[$codeStore]['cotations'][$hashCotation]['results'] = $response;

            }

            if ($all == 'cache') {
                return $_SESSION[$codeStore]['cotations'][$hashCotation]['results'];
            }

            if (!$all && !empty($services)) {
                foreach ($services as $service) {
                    if (isset($_SESSION[$codeStore]['cotations'][$hashCotation]['results'][$service])) {
                        return $_SESSION[$codeStore]['cotations'][$hashCotation]['results'][$service];
                    }
                }
            }

            return false;
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
                if (isset($method->code)) {
					return $method->code;
				}
				return null;
            }
        }
        
        return null;
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