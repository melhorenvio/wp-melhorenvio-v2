<?php

namespace Models;

use Bases\bOrders;
use Controllers\CotationController;

class Order extends bOrders {
    
    private $id;
    private $products;
    private $total;
    private $total_shipping;
    private $to;
    private $cotation;
    private $status;
    private $address;

    public function __construct($id = null)
    {
        $post = get_post($id);

        $orderWc = new \WC_Order( $id );
        
        $data = $orderWc->get_data();

        $this->id = $id;
        
        $this->address = $data['shipping'];
        
        $this->products = $this->getProducts();
        
        $this->total = $orderWc->total;
        
        $this->shipping_total = $orderWc->shipping_total;
        
        $this->to = $data['billing'];
        
        $this->cotation = $this->getCotation();
    }

    /**
     * Retrieve One Order by its ID.
     *
     * @param [Int] $id
     * @return object
     */
    public function retrieveOne() : Array
    {
        return [
            'method' => 'OrdersModel@retrieveOne',
            'data' => $this
        ];
    }


     /**
     * @param Array $filters
     * @return Array
     */
    public function getAllOrders(Array $filters = NULL) : Array
    {
        $args = [
            'numberposts' => ($filters['limit']) ?: 10,
            'offset' => ($filters['skip']) ?: 0,
            'post_status' => 'public',
            'post_type' => 'shop_order',
        ];

        if (isset($filters['status']) && $filters['status'] != 'all') {
            $args['meta_query'] = [
                [
                    'key' => 'melhorenvio_status_v2',
                    'value' => sprintf(':"%s";', $filters['status']),
                    'compare' => 'LIKE'
                ]
            ];
        }

        $posts =  get_posts($args);

        $data = [];
        foreach ($posts as $post) {

            $order = new Order($post->ID);

            $dataMelhorEnvio = $order->getDataOrder();

            $data[] =  [
                'id' => $order->id,
                'total' => $order->total,
                'products' => $order->getProducts(),
                'cotation' => $order->getCotation(),
                'address' => $order->address,
                'to' => $order->to,
                'status' => $dataMelhorEnvio['status'],
                'order_id' => $dataMelhorEnvio['order_id'],
                'protocol' => $dataMelhorEnvio['protocol']
            ];
        }

        return $data;
    }

    /**
     * Retrieve all products in Order.
     *
     * @param [Int] $id
     * @return object
     */
    protected function getProducts() {
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
    public function getCotation($id = null) {

        if ($id) $this->id = $id; 

        // TODO caso já pago, não cotar novamente.
        $cotation = get_post_meta($this->id, 'melhorenvio_cotation_v2', true);
        $end_date = date("Y-m-d H:i:s", strtotime("- 7 days")); 

        if (!$cotation or empty($cotation) or  $cotation['date_cotation'] <= $end_date) {
            $cotationController = new CotationController();
            return  $cotationController->makeCotationOrder($this->id);
        }
        return $cotation;
    }    

    private function getDataOrder($id = null) {
        if ($id) $this->id = $id; 
        $data = end(get_post_meta($this->id, 'melhorenvio_status_v2'));
        $default = [
            'status' => null,
            'order_id' => null,
            'protocol' => null
        ];

        if (empty($data) || !$data) {
            return $default;
        }

        return $data;
    }
}   