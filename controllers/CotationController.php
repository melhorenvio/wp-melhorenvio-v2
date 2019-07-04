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
use Models\Log;
use Models\Quotation;
use Models\Method;

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
        $q = (new Quotation($order_id));

        $result = $q->calculate();
    
        global $woocommerce;

        $totalCart = 0;
        $freeShipping = false;
        foreach(WC()->cart->cart_contents as $cart) {
            $totalCart += $cart['line_subtotal'];
        }

        // Utilizado frete grátis?
        foreach(WC()->cart->get_coupons() as $cp) {
            if ($cp->get_free_shipping() && $totalCart >= $cp->amount ) {
                $freeShipping = true;
            }
        }

        $result['date_cotation'] = date('Y-m-d H:i:d'); 
        $result['choose_method'] = (new Method($order_id))->getMethodShipmentSelected($order_id);
        $result['free_shipping'] = $freeShipping; 
        $result['total']         = $total;
        
        add_post_meta($order_id, 'melhorenvio_cotation_v2', $result);

        return $result;
    }

    public function refreshCotation()
    {
        $results = $this->makeCotationOrder($_GET['id']);
        echo json_encode($results);
        die;
    }

    /**
     * @return void
     */
    public function cotationProductPage() 
    {
        if (!isset($_POST['data'])) {
            return array(
                'success' => false,
                'message' => 'Dados incompletos'
            );
        }

        if (!isset($_POST['data']['cep_origem'])) {
            return array(
                'success' => false,
                'message' => 'Campo CEP é necessário'
            );
        }

        $package = array( 
            'destination'  => array(
                'country'  => 'BR',
                'state'    => 'RS',
                'postcode' => $_POST['data']['cep_origem'] 
            ),
            'cotationProduct' => array(
                (object) array(
                    'id'                 =>  $_POST['data']['id_produto'],
                    "weight"             =>  $_POST['data']['produto_peso'],
                    "width"              =>  floatval($_POST['data']['produto_largura']),
                    "length"             =>  floatval($_POST['data']['produto_comprimento']),
                    "height"             =>  floatval($_POST['data']['produto_altura']),
                    'quantity'           =>  intval($_POST['data']['quantity']),
                    'price'              =>  floatval($_POST['data']['produto_preco']),
                    'notConverterWeight' => true 
                )
            )
        );

        $shipping_zone = \WC_Shipping_Zones::get_zone_matching_package( $package );
        
        $shipping_methods = $shipping_zone->get_shipping_methods( true );

        $rates = array();
        
        $free = 0;

        foreach($shipping_methods as $keyMethod => $shipping_method) {
    
            $rate = $shipping_method->get_rates_for_package( $package );

            if (key($rate) == 'free_shipping') {
                $free++;
            }

            if (empty($rate) || (key($rate) == 'free_shipping') && $free > 1 ) {
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
            'delivery_time' => (new TimeController)->setLabel($item->meta_data['delivery_time'], $item->get_id()),
            'added_extra' => false
        ];
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

    public function freeShipping()
    {
        global $woocommerce;

        $totalCart = 0;

        $freeShiping = false;

        foreach(WC()->cart->cart_contents as $cart) {
            $totalCart += $cart['line_subtotal'];
        }

        foreach(WC()->cart->get_coupons() as $cp) {
            if ($cp->get_free_shipping() && $totalCart >= $cp->amount ) {
                $freeShiping = true;
            }
        }

        if ($freeShiping) {
            return array(
                'id' => 'free_shipping',
                'label' => 'Frete grátis',
                'cost' => '',
                'calc_tax' => 'per_item',
                'meta_data' => array(
                    'delivery_time' => '',
                    'company' => ''
                )
            );
        }

        return false;
    }

    public function checkCotationTest()
    {
        // if (!isset($_GET['cep'])) {
        //     echo json_encode([
        //         'error' => 'Informar o cep de destino'
        //     ]);
        //     die;
        // }

        // $response['cep_destiny'] = $_GET['cep'];

        // $response['token'] = get_option('wpmelhorenvio_token');

        // $params = array(
        //     'headers'=> array(
        //         'Content-Type' => 'application/json',
        //         'Accept'=>'application/json',
        //         'Authorization' => 'Bearer '.$response['token']
        //     )
        // );

        // $response['account'] = wp_remote_retrieve_body(
        //     wp_remote_get('https://api.melhorenvio.com/v2/me', $params)
        // );

        // $response['package'] = [
        //     'width'  => (isset($_GET['width']))  ? (float) $_GET['width']  : 17 ,
        //     'height' => (isset($_GET['height'])) ? (float) $_GET['height'] : 23,
        //     'length' => (isset($_GET['length'])) ? (float) $_GET['length'] : 10,
        //     'weight' => (isset($_GET['weight'])) ? (float) $_GET['weight'] : 1
        // ];


        // $options['insurance_value'] = (isset($_GET['insurance_value']))  ? (float) $_GET['insurance_value']  : 20.50;

        // $response['insurance_value'] = (isset($_GET['insurance_value']))  ? (float) $_GET['insurance_value']  : 20.50;

        // $response['user']   = (new UsersController())->getInfo();

        // $response['origem'] = (new UsersController())->getFrom();

        // // $response['cotation'] = (new CotationController())->makeCotationPackage($response['package'], [1,2,3,4,9], $response['cep_destiny'], $options);

        // $response['plugins_instaled'] = apply_filters( 'network_admin_active_plugins', get_option( 'active_plugins' ));

        // $response['is_multisite'] = is_multisite();

        // $response['enableds'] = (new Method())->getArrayShippingMethodsEnabledByZoneMelhorEnvio();

        // $response['session'] = $_SESSION;

        // $response['path'] = plugin_dir_path( __FILE__ ) . 'services/*.php'  ;

        // foreach ( glob( plugin_dir_path( __FILE__ ) . '/services/*.php' ) as $filename ) {
        //     $response['servicesFile'][] = $filename;
        // }

        // echo json_encode($response);
        // die;
    }
}

$cotationcontroller = new CotationController();
