<?php

namespace Models;

use Models\Order;
use Models\User;
use Models\Option;
use Models\Log;
use Models\Method;
use Models\Address;
use Controllers\TokenController;
use Controllers\HelperController;

class Quotation 
{  
    private $id;

    private $from;

    private $to;

    private $products;

    private $insurance_value;

    private $options;

    private $hashCotation;

    private $codeStore;

    public $response;

    const URL = 'https://q-engine-hub.melhorenvio.com';

    public function __construct($id = null, $products = array(), $package = array(), $to = null)
    {
        $this->id = $id;
        $this->from            = $this->getFrom();

        if (!is_null($id)) {
            $this->products        = $this->getProducts();    
            $this->to              = $this->getTo();
            $this->insurance_value = $this->getInsuranceValue();
            
        }

        if (!empty($products) || !empty($package) ) {
            $this->products = $products;
        }

        if (!is_null($to)) {
            $this->to = $to;
        }

        $this->options         = $this->getOptions();
        $this->codeStore       = md5(get_option('home'));
    }

    /**
     * Return an object From data quotation
     *
     * @return Object
     */
    public function getFrom()
    {
        try {

            $user = (new User())->get();

            $address = (new Address())->getAddressFrom();

            if (!$user['success'] || !$address['success']) {
                return false;
            }

            $user = $user['data'];

            return (object) array(
                'name'     => $user['firstname'] . ' ' . $user['lastname'],
                'email'    => $user['email'],
                'document' => $user['document'],
                'phone'    => $user['phone']->phone,
                'address'  => (object) $address['address']
            );

        } catch (Exception $e) {
            // tratar log 
        }
    }

    /**
     * Return an object To data quotation
     *
     * @return Object
     */
    public function getTo()
    {
        try {

            $orderWc = new \WC_Order( $this->id );

            $to = $orderWc->get_data();

            $to = $to['shipping'];

            return (object) $to;

        } catch (Exception $e) {
            // tratar log
        }
    }
    
    /**
     * Return an array with the products by quotation
     *
     * @return Array
     */
    public function getProducts()
    {
        $products = [];

        try {

            $orderWc = new \WC_Order( $this->id );

            $order_items = $orderWc->get_items();
            
            foreach ($order_items as $product) {

                $data = $product->get_data();
                
                $productId = ($data['variation_id'] != 0) ? $data['variation_id'] : $data['product_id'];

                $productInfo = wc_get_product( $productId );

                $products[] = (object) array(
                    'id'           => $data['product_id'],
                    'variation_id' => $data['variation_id'],
                    'name'         => $data['name'],
                    'price'        => $productInfo->get_price(),
                    'height'       => $productInfo->get_height(),
                    'width'        => $productInfo->get_width(),
                    'length'       => $productInfo->get_length(),
                    'weight'       => $productInfo->get_weight(),
                    'quantity'     => intval($data['quantity']),
                    'total'        => floatval($data['total'])
                );
            }

        } catch (Exception $e) {
            // Tratar log aqui
        }

        return $products;
    }

    /**
     * Return a insurance value by quotation
     *
     * @return flaot
     */
    public function getInsuranceValue()
    {
        try {

            $orderWc = new \WC_Order( $this->id );

            $data = $orderWc->get_data();

            return floatval($data['total']);
        
        } catch (Exception $e) {
            // tratar log
        }
    }

    /**
     * Return a object with the options quotation
     *
     * @return Object
     */
    public function getOptions()
    {
        try {
            return (new Option())->getOptions();
        } catch (Exception $e) {
            // tratar log
        }
    }

    /**
     * Create a body to make a quotation
     *
     * @return Array
     */
    private function prepareBody()
    {
        $options = array(
            "receipt"         => $this->options->ar, 
            "own_hand"        => $this->options->mp, 
            "collect"         => false 
        );

        if (!isset($this->from->address->postal_code)) {
            return null;
        }

        $body = array(
            'from' => array(
                'postal_code' => $this->from->address->postal_code
            ),
            'to' => array(
                'postal_code' => str_replace('-', '', (isset($this->to->postcode)) ? $this->to->postcode : $this->to)
            ),
            'settings' => array(
                'show' => array( 
                    'price' => true,
                    'discount' => true,
                    'delivery' => true
                )
            )
        );

        $body['options'] = $options;

        $insurance_value = [];

        if (!empty($this->products)) {
            foreach ($this->products as $key => $product) {

                $body['products'][$key] = array(
                    'id'        => $product->id,
                    'quantity'  => intval($product->quantity)
                );

                $helper = new HelperController();

                $body['products'][$key]['volumes'][] = array(
                    'height' => $helper->converterDimension($product->height),
                    'width'  => $helper->converterDimension($product->width),
                    'length' => $helper->converterDimension($product->length),
                    'weight' => (isset($product->notConverterWeight)) ? round($product->weight,2) : round($helper->converterIfNecessary($product->weight),2)
                );

                $insurance_value[$key] = floatval($product->price);
            }
        }

        foreach ($insurance_value as $key => $value) {
            $body['products'][$key]['insurance'] = round($value, 2);
        }

        if (!empty($package)) {
            $body['volumes'][] = $package;
        }

        return $body;
    }

    /**
     * Function to make a quotation on API Melhor Envio
     *
     * @return Array
     */
    public function calculate($service = null)
    {
        $token = (new TokenController())->token();

        if (!empty($token) || !is_null($token)) {

            $body = $this->prepareBody();

            $params = array(
                'headers'           =>  array(
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                    'Authorization' => 'Bearer '.$token,
                ),
                'body'   => json_encode($body),
                'timeout'=> 10
            );

            // var_dump(json_encode($body));die;

            $this->hashCotation = md5(json_encode($body));

            if (!isset($_SESSION[$this->codeStore]['cotations'][$this->hashCotation]['results'])) {

                try {
                    $response = json_decode(
                        wp_remote_retrieve_body(
                            wp_remote_post(self::URL . '/api/v1/calculate', $params)
                        )
                    );

                    if (empty($response)) {
                        return false;
                    }

                    (new Log())->register($this->id, 'make_cotation', $body, $response);

                    $filterCotations = array();
                    foreach ($response as $item) {

                        if (isset($item->error)) {
                            (new Log())->register(
                                $this->id, 'error_cotation', $body, [
                                    'service' => $item->id,
                                    'error' => $item->error
                                ]
                            );
                            continue;
                        }
                        $filterCotations[$item->id] = $item;
                    }

                    if (empty($filterCotations)) {
                        return false;
                    }

                    $_SESSION[$this->codeStore]['cotations'][$this->hashCotation] = [
                        'created' => date('Y-m-d h:i:s'),
                        'results' => $filterCotations
                    ];

                    if (!is_null($service)) {
                        return $_SESSION[$this->codeStore]['cotations'][$this->hashCotation]['results'][$service];
                    }

                    return $_SESSION[$this->codeStore]['cotations'][$this->hashCotation]['results'];

                } catch (Exception $e) {
                    return false;
                }
            }
        } 

        if (!is_null($service)) {
            return $_SESSION[$this->codeStore]['cotations'][$this->hashCotation]['results'][$service];
        }

        return $_SESSION[$this->codeStore]['cotations'][$this->hashCotation]['results'];
    }
}
