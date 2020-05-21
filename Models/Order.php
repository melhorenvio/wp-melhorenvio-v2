<?php

namespace Models;

use Services\OrderQuotationService;

class Order {

    const ROUTE_MELHOR_ENVIO_TRACKING = '/shipment/tracking';

    private $id;
    private $products;
    private $total;
    private $shipping_total;
    private $to;
    private $cotation;
    private $address;

    /**
     * @param int $id
     */
    public function __construct($id = null)
    {
        try {
            $post = get_post($id);

            $orderWc = new \WC_Order( $id );
            
            $data = $orderWc->get_data();

            $this->id = $id;
            
            $this->address = $data['shipping'];
            
            $this->products = $this->getProducts();
            
            $this->total = 0; //$orderWc->total;
            
            $this->shipping_total = 0; //$orderWc->shipping_total;
            
            $this->to = $data['billing'];
            
            $this->cotation = (array) $this->getCotation();
            
        } catch (\Exception $e) {
            
        }

    }

    /**
     * Retrieve all products in Order.
     *
     * @param [Int] $id
     * @return object
     */
    protected function getProducts() 
    {
        $orderWc = new \WC_Order( $this->id );
        $order_items = $orderWc->get_items();
        $products = [];
        foreach ($order_items as $product) {
            $data = $product->get_data();
            $products[] = (object) [
                'id' => $data['product_id'],
                'variation_id' => $data['variation_id'],
                'name' => $data['name'],
                'quantity' => $data['quantity'],
                'total' => $data['total']
            ];
        }
        return $products;
    }

    /**
     * Retrieve cotation.
     *
     * @param [Int] $id
     * @return object
     */
    public function getCotation($id = null) 
    {
        if ($id) $this->id = $id; 

        return (new OrderQuotationService())->getQuotation($this->id);
    }    

    /**
     * @param [type] $id
     * @param [type] $invoices
     * @return void
     */
    public function updateInvoice($id, $invoices) 
    {
        var_dump('deprecado usar InvoiceService');die;
    }   

}   
