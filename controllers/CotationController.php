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
        $result['total']         = $total; // var não definida

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
            echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
            exit();
        }

        if (!isset($_POST['data']['cep_origem'])) {
            echo json_encode(['success' => false, 'message' => 'Campo CEP é necessário']);
            exit();
        }

        if ( strlen(trim($_POST['data']['cep_origem'])) != 9 ) {
            echo json_encode(['success' => false, 'message' => 'Campo CEP precisa ter 8 digitos']);
            exit();
        }

        $destination = $this->getAddressByCep($_POST['data']['cep_origem']);
        if(empty($destination) || is_null($destination)) {
            echo json_encode(['success' => false, 'message' => 'CEP inválido ou não encontrado']);
            exit();
        }      
 
        $package = array( 
            'ship_via'     => '',
            'destination'  => array(
                    'country'  => 'BR',
                    'state'    => $destination->uf,
                    'postcode' => $destination->cep, 
                ),
            'cotationProduct' => array(
                (object) array(
                    'id'                 => $_POST['data']['id_produto'],
                    "weight"             => floatval($_POST['data']['produto_peso']),
                    "width"              => floatval($_POST['data']['produto_largura']),
                    "length"             => floatval($_POST['data']['produto_comprimento']),
                    "height"             => floatval($_POST['data']['produto_altura']),
                    'quantity'           => intval($_POST['data']['quantity']),
                    'price'              => floatval($_POST['data']['produto_preco']),
                    'notConverterWeight' => true 
                )
            )
        );

        $shipping_zone = \WC_Shipping_Zones::get_zone_matching_package( $package );
        $shipping_methods = $shipping_zone->get_shipping_methods( true );
        if(count($shipping_methods) == 0) {
            echo json_encode(['success' => false, 'message' => 'Não é feito envios para o CEP informado']);
            exit();
        }

        $rates = array();        
        $free = 0;
        foreach($shipping_methods as $shipping_method) 
        {
            $rate = $shipping_method->get_rates_for_package( $package );
            if (key($rate) == 'free_shipping') {
                $free++;
            }

            if (empty($rate) || (key($rate) == 'free_shipping') && $free > 1 ) {
                continue;
            }

            $rates[] = $this->mapObject($rate[key($rate)]);
        }   

        echo json_encode(['success' => true, 'data' => $rates]);
        exit();
    }

    /**
     * Get address information from zip code
     *
     * @param [string] $cep
     * @return Json
     */
    private function getAddressByCep($cep)
    {
        if(empty($cep)) return null;

        $url = "https://location.melhorenvio.com.br/". str_replace('-', '', trim($cep));
        
        $curl = curl_init(); 
        curl_setopt($curl, CURLOPT_URL, $url); 
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
        $result = curl_exec($curl); 
        $error  = curl_error($curl);
        curl_close($curl); 

        if(!empty($error)) return null;

        $response = json_decode($result);       
        return $response;
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
}

$cotationcontroller = new CotationController();
