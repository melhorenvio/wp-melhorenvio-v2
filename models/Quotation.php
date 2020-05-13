<?php

namespace Models;

use Models\Order;
use Models\User;
use Models\Option;
use Models\Log;
use Models\Method;
use Models\Address;
use Helpers\DimensionsHelper;
use Services\QengineService;
use Services\QuotationService;
use Services\OrdersProductsService;

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

    public function __construct($id = null, $products = array(), $package = array(), $to = null)
    {
        $this->id = $id;

        $this->from = $this->getFrom();

        if (!is_null($id)) {
            $this->products = $this->getProducts();
            $this->to = $this->getTo();
            $this->insurance_value = $this->getInsuranceValue();
            
        }

        if (!empty($products) || !empty($package) ) {
            $this->products = $products;
        }

        if (!is_null($to)) {
            $this->to = $to;
        }

        $this->options = $this->getOptions();
        $this->codeStore = md5(get_option('home'));
    }

    /**
     * Return an object From data quotation
     *
     * @return Object|void
     */
    public function getFrom()
    {
        try {
            $user = (new User())->get();

            $address = (new Address())->getAddressFrom();

            if ($user['success'] && $address['success']) {
                $user = $user['data'];

                return (object) array(
                    'name'     => $user['firstname'] . ' ' . $user['lastname'],
                    'email'    => $user['email'],
                    'document' => $user['document'],
                    'phone'    => $user['phone']->phone,
                    'address'  => (object) $address['address']
                );
            }
        } catch (\Exception $e) {
            // tratar log 
        }
    }

    /**
     * Return an object To data quotation
     *
     * @return Object|void
     */
    public function getTo()
    {
        try {
            $orderWc = new \WC_Order( $this->id );

            $to = $orderWc->get_data();

            $to = $to['shipping'];

            return (object) $to;
        } catch (\Exception $e) {
            // tratar log
        }
    }
    
    /**
     * Return an array with the products by quotation
     *
     * @return array|void
     */
    public function getProducts()
    {
        return (new OrdersProductsService())->getProductsOrder($this->id);
    }

    /**
     * Return a insurance value by quotation
     *
     * @return float|void
     */
    public function getInsuranceValue()
    {
        try {
            $orderWc = new \WC_Order( $this->id );

            $data = $orderWc->get_data();

            return floatval($data['total']);
        } catch (\Exception $e) {
            // tratar log
        }
    }

    /**
     * Return a object with the options quotation
     *
     * @return Object|void
     */
    public function getOptions()
    {
        try {
            return (new Option())->getOptions();
        } catch (\Exception $e) {
            // tratar log
        }
    }

    /**
     * Create a body to make a quotation
     *
     * @return array
     */
    private function prepareBody()
    {
        var_dump('DEPRETACO QUOTATION LINE 189 - prepareBody');die;
        /**$options = array(
            'receipt' => $this->options->ar,
            'own_hand' => $this->options->mp,
            'collect'  => false
        );

        if (!isset($this->from->address->postal_code)) {
            return null;
        }

        $from = $this->from->address->postal_code;

        if (is_object($from) || is_array($from)) {
            return null;
        }

        $to = isset($this->to->postcode) ? $this->to->postcode : $this->to;

        if (is_object($to) || is_array($to)) {
            return null;
        }

        $body = array(
            'from' => array(
                'postal_code' => preg_replace('/\D/', '', $from),
            ),
            'to' => array(
                'postal_code' => preg_replace('/\D/', '', $to),
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
                    'name'      => $product->name,
                    'quantity'  => intval($product->quantity)
                );

                $body['products'][$key]['volumes'][] = array(
                    'height' => (int) (new DimensionsHelper())->converterDimension($product->height),
                    'width'  => (int) (new DimensionsHelper())->converterDimension($product->width),
                    'length' => (int) (new DimensionsHelper())->converterDimension($product->length),
                    'weight' => (float) (isset($product->notConverterWeight)) ? round($product->weight,2) : round((new DimensionsHelper())->converterIfNecessary($product->weight),2)
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

        return $body;*/
    }

    /**
     * Function to make a quotation on API Melhor Envio
     *
     * @param null $service
     * @return array|boolean
     */
    public function calculate($service = null)
    {
        var_dump('Deprecado use QuotationService@calculateByProducts');die;
        /**if ($body = $this->prepareBody()) {

            $this->hashCotation = md5(json_encode($body));

            //if (!isset($_SESSION[$this->codeStore]['cotations'][$this->hashCotation]['results'])) {

                try {

                    echo '<pre>';
                    var_dump($this->products);die;

                    //$response = (new QuotationService())->calculateQuotationByProducts();

                    //var_dump($response);die;

                    if (empty($response)) {
                        return false;
                    }



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

                    if (!is_null($service) && !empty($_SESSION[$this->codeStore]['cotations'][$this->hashCotation]['results'][$service])) {
                        return $_SESSION[$this->codeStore]['cotations'][$this->hashCotation]['results'][$service];
                    }

                    return $_SESSION[$this->codeStore]['cotations'][$this->hashCotation]['results'];

                } catch (\Exception $e) {
                    return false;
                }
            }
        //} 

        if (!is_null($service) && isset($_SESSION[$this->codeStore]['cotations'][$this->hashCotation]['results'][$service])) {
            return $_SESSION[$this->codeStore]['cotations'][$this->hashCotation]['results'][$service];
        }

        return $_SESSION[$this->codeStore]['cotations'][$this->hashCotation]['results']; */
    }
}
