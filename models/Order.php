<?php

namespace Models;

use Controllers\CotationController;

class Order {
    
    const URL = 'https://api.melhorenvio.com';

    private $id;
    private $products;
    private $total;
    private $total_shipping;
    private $to;
    private $cotation;
    private $status;
    private $address;

    /**
     * @param [type] $id
     */
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
     * @param Array $filters
     * @return Array
     */
    public function getAllOrders($filters = NULL)
    {
        $args = [
            'numberposts' => ($filters['limit']) ?: 5,
            'offset' => ($filters['skip']) ?: 0,
            'post_status' => ($filters['wpstatus']) ?: 'public',
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

        if (empty($posts)) {
            return [
                'orders' => [],
                'load' => false
            ];
        }

        $data = [];
        $orders = [];
        foreach ($posts as $post) {

            $order = new Order($post->ID);

            $dataMelhorEnvio = $order->getDataOrder(); 

            $cotation = $order->getCotation();

            $invoice = $order->getInvoice();

            $non_commercial = true;
            if (!is_null($invoice['number']) && !is_null($invoice['key']) ) {
                $non_commercial = false;
            }
            
            if (!is_null($dataMelhorEnvio['order_id'])) {
                $orders[] = $dataMelhorEnvio['order_id'];
            }

            $data[] =  [
                'id' => (int) $order->id,
                'total' => 'R$' . number_format($order->total, 2, ',', '.'),
                'products' => $order->getProducts(),
                'cotation' => $cotation ,
                'address' => $order->address,
                'to' => $order->to,
                'status' => $dataMelhorEnvio['status'],
                'order_id' => $dataMelhorEnvio['order_id'],
                'protocol' => $dataMelhorEnvio['protocol'],
                'non_commercial' => $non_commercial,
                'invoice' => $invoice,
                'packages' => $order->mountPackage($cotation)
            ];
        }

        $data = $order->matchStatus($data, $orders);

        $load = false;
        if(count($data) == ($filters['limit']) ?: 5) {
            $load = true;
        }

        $response = [
            'orders' => $data,
            'load' => $load
        ];

        return $response;
    }

    // TODO refator para usar esse "getOne" na função acima.
    public function getOne($id)
    {
        $order = new Order($id);

        $dataMelhorEnvio = $order->getDataOrder(); 

        $cotation = $order->getCotation();

        $invoice = $order->getInvoice();

        $non_commercial = true;
        if (!is_null($invoice['number']) && !is_null($invoice['key']) ) {
            $non_commercial = false;
        }
        
        if (!is_null($dataMelhorEnvio['order_id'])) {
            $orders[] = $dataMelhorEnvio['order_id'];
        }

        $data =  [
            'id' => $order->id,
            'total' => 'R$' . number_format($order->total, 2, ',', '.'),
            'products' => $order->getProducts(),
            'cotation' => $cotation ,
            'address' => $order->address,
            'to' => $order->to,
            'status' => 'pending',
            'order_id' => $dataMelhorEnvio['order_id'],
            'protocol' => $dataMelhorEnvio['protocol'],
            'non_commercial' => $non_commercial,
            'invoice' => $invoice,
            'packages' => $order->mountPackage($cotation)
        ];

        return $data;
    }

    private function mountPackage($cotation)
    {
        $response = null;
        foreach($cotation as $item){

            if(is_null($item->id)) {
                continue;
            }

            foreach($item->packages as $key => $package) {
                $response[$item->id] = (object) [
                    'largura' => $package->dimensions->width,
                    'altura' => $package->dimensions->height,
                    'comprimento' => $package->dimensions->length,
                    'peso' => $package->weight
                ];
            }
        }

        return $response;
    }

    /**
     * @param [type] $posts
     * @param [type] $orders
     * @return void
     */
    private function matchStatus($posts, $orders) 
    {
        //63ef5eff-095d-484a-a8e2-b28b55173b77

        $statusApi = $this->getStatusApi($orders);   

        foreach ($posts as $key => $post) {

            foreach ($post['order_id'] as $order_id) {

                if (array_key_exists($order_id, $statusApi)) {
                    if ($post['status'] != $statusApi[$order_id]) {

                        $st = $statusApi[$order_id];
                        if ($st == 'released') {
                            $st = 'paid';
                        }

                        if ($st == 'canceled') {
                            $st = null;
                        }
                        $posts[$key]['status'] = $st;
                        
                    }
                    continue;
                }
                $posts[$key]['status'] = null;
            }
        }
        return $posts;
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

        $cotation = get_post_meta($this->id, 'melhorenvio_cotation_v2');

        $cotation = end($cotation);

        $end_date = date("Y-m-d H:i:s", strtotime("- 7 days")); 

        if (!$cotation or empty($cotation) or is_null($cotation) or  $cotation['date_cotation'] <= $end_date) {
            $cotation = (new CotationController())->makeCotationOrder($this->id);
            return $cotation;
        }

        return $cotation;
    }    

    /**
     * @param [type] $id
     * @return void
     */
    private function getOldChooseMethod($id) 
    {
        $oldChooseMethod = 0;
        $oldCot = get_post_meta($id, 'cotacao_melhor_envio', true);

        if(!empty($oldCot)) {
            foreach($oldCot as $item) {
                if($item['selected']) {
                    $oldChooseMethod = $item['id'];
                }
            }
        }
        return $oldChooseMethod;
    }

    /**
     * @param [type] $id
     * @return void
     */
    private function getOldstatus($id) 
    {
        global $wpdb;
        $result = null;
        $sql = sprintf("SELECT * FROM %stracking_codes_wpme WHERE order_id = '%s' ORDER BY id DESC LIMIT 1", $wpdb->prefix, $id);
        $result = $wpdb->get_results($sql);
        if(!empty($result)){
            $result = end($result);
            $result = $result->status;
            if ($result == 'removed' || $result == 'waiting') {
                return null;
            }

            return $result;
        }
        return null;
    }

    /**
     * @param [type] $id
     * @return void
     */
    private function getDataOrder($id = null) 
    {
        if ($id) $this->id = $id; 

        $data = end(get_post_meta($this->id, 'melhorenvio_status_v2'));
        $status = null;
        if ($data == false) {
            $status = $this->getOldstatus($this->id);
        }

        $default = [
            'status' => $status,
            'order_id' => null,
            'protocol' => null
        ];

        if (empty($data) || !$data) {
            return $default;
        }

        if (!is_array($data['protocol'])) {
            $data['protocol'] = (Array) $data['protocol'];
        }

        if (!is_array($data['order_id'])) {
            $data['order_id'] = (Array) $data['order_id'];
        }

        return $data;
    }

    /**
     * @param [type] $id
     * @param [type] $invoices
     * @return void
     */
    public function updateInvoice($id, $invoices) 
    {
        $oldData = end(get_post_meta($id, 'melhorenvio_invoice_v2', true));
        if (empty($oldData || is_null($oldData))) {
            $invoices = array_merge($oldData, $invoices);
        }
        delete_post_meta($id, 'melhorenvio_invoice_v2');
        add_post_meta($id, 'melhorenvio_invoice_v2', $invoices);

        return [
            'success' => true
        ];
    }   

    /**
     * @param [type] $id
     * @return void
     */
    private function getInvoice($id = null) 
    {
        if ($id) $this->id = $id; 
        $data = end(get_post_meta($this->id, 'melhorenvio_invoice_v2'));
        $default = [
            'number' => null,
            'key' => null
        ];

        if (empty($data) || !$data) {
            return $default;
        }
        return $data;
    }

    /**
     * @param [type] $orders
     * @return void
     */
    private function getStatusApi($orders) 
    {

        $arrayOrders = [];
        foreach ($orders as $items) {
            foreach($items as $order){
                $arrayOrders[] = $order;
            }
        }

        if ($token = get_option('wpmelhorenvio_token')) {
            $body = [
                "orders" => $arrayOrders
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
                    wp_remote_post(self::URL . '/v2/me/shipment/tracking', $params)
                )
            );

            if(isset($response->errors)) {
                return null;
            }

            $data = [];
            foreach($response as $order) {
                $data[$order->id] = $order->status;
            }
            return $data;
        }
        return null;
    }
}   
