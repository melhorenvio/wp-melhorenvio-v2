<?php

namespace Models;

use Models\User;
use Models\Option;
use Models\Address;
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
}
