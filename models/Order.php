<?php

namespace Models;

use Controllers\CotationController;
use Controllers\LogsController;

class Order {
    
    const URL = 'https://api.melhorenvio.com';

    private $id;
    private $products;
    private $total;
    private $shipping_total;
    private $to;
    private $cotation;
    //private $status;
    private $address;

    /**
     * @param [type] $id
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
            
            $this->cotation = $this->getCotation();
        } catch (Exception $e) {
            
        }

    }

    /**
     * @param Array $filters
     * @return Array
     */
    public static function getAllOrders($filters = NULL)
    {
        $args = [
            'numberposts' => ($filters['limit']) ?: 5,
            'offset'      => ($filters['skip']) ?: 0,
            'post_type'   => 'shop_order',
        ];

        if(isset($filters['wpstatus']) && $filters['wpstatus'] != 'all'){
            $args['post_status'] = $filters['wpstatus'];
        } else if(isset($filters['wpstatus']) && $filters['wpstatus'] == 'all') {
            $args['post_status'] = array_keys( wc_get_order_statuses() );
        } else {
            $args['post_status'] = 'publish';
        }

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
            return ['orders' => [], 'load' => false];
        }

        $data = [];
        $orders = [];
        foreach ($posts as $post) {

            try {
                $order = new Order($post->ID);
                
                $dataMelhorEnvio = $order->getDataOrder(); 

                $cotation = $order->getCotation();

                $invoice = $order->getInvoice();

                $statusTranslate = $order->translateNameStatus($dataMelhorEnvio['status']);

                $non_commercial = true;
                if (!is_null($invoice['number']) && !is_null($invoice['key']) ) {
                    $non_commercial = false;
                }
                
                if (!is_null($dataMelhorEnvio['order_id'])) {
                    $orders[] = $dataMelhorEnvio['order_id'];
                }

                $data[] =  [
                    'id'             => (int) $order->id,
                    'total'          => 'R$' . number_format($order->total, 2, ',', '.'),
                    'products'       => $order->getProducts(),
                    'cotation'       => $cotation,
                    'address'        => $order->address,
                    'to'             => $order->to,
                    'status'         => $dataMelhorEnvio['status'],
                    'status_texto'   => $statusTranslate,
                    'order_id'       => $dataMelhorEnvio['order_id'],
                    'protocol'       => $dataMelhorEnvio['protocol'],
                    'non_commercial' => $non_commercial,
                    'invoice'        => $invoice,
                    'packages'       => $order->mountPackage($cotation),
                    'link'           => admin_url() . sprintf('post.php?post=%d&action=edit', $order->id),
                    'log'            => admin_url() . sprintf('admin.php?page=melhor-envio#/log/%s', $order->id),
                    'errors'         => get_post_meta($order->id, 'melhorenvio_errors', true)
                ];
                
            } catch(Exception $e) {
                (new LogsController)->add(
                    null, 
                    'Get Order', 
                    [], 
                    $e->getMessage(), 
                    'CotationController', 
                    'makeCotationOrder', 
                    'https://api.melhorenvio.com/v2/me/shipment/calculate'
                );        
            }
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

        if (empty($cotation) || is_null($cotation)) {
            return $response;
        }
        
        foreach($cotation as $item){

            if(is_null($item->id) || !isset($item->id)) {
                continue;
            }
            
            if (isset($item->packages)) {

                foreach($item->packages as $key => $package) {
                    $response[$item->id] = (object) [
                        'largura' => $package->dimensions->width,
                        'altura' => $package->dimensions->height,
                        'comprimento' => $package->dimensions->length,
                        'peso' => $package->weight
                    ];
                }

            } elseif (isset($item->volumes)) {

                foreach($item->volumes as $key => $volume) {
                    $response[$item->id] = (object) [
                        'largura' => $volume->width,
                        'altura' => $volume->height,
                        'comprimento' => $volume->length,
                        'peso' => $volume->weight
                    ];
                }

            } else {
                continue;
            }
            
        }

        return $response;
    }

    public function getTo()
    {
        
    }

    /**
     * @param [type] $posts
     * @param [type] $orders
     * @return void
     */
    private function matchStatus($posts, $orders) 
    {
        $statusApi = $this->getStatusApi($orders);                
        foreach ($posts as $key => $post) {

            if (!empty($post['order_id'])) {
                foreach ($post['order_id'] as $order_id) {                

                    if (array_key_exists($order_id, $statusApi)) {
                        if ($post['status'] != $statusApi[$order_id]['status']) {

                            $st = $statusApi[$order_id]['status'];
                            if ($st == 'released') {
                                $st = 'paid';
                            }

                            if ($st == 'canceled') {
                                $st = null;
                            }
                            $posts[$key]['status'] = $st;                                               
                        }
                        
                    } else {
                        $posts[$key]['status'] = null;  
                    }                                       
                }
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

        $cotations = get_post_meta($this->id, 'melhorenvio_cotation_v2');

        $cotation = end($cotations);

        if(date('Y-m-d H:i:s', strtotime('+24 hours', strtotime($cotation['date_cotation']))) < date("Y-m-d h:i:s")) {

            $cotation = (new CotationController())->makeCotationOrder($this->id);
            return $this->setIndexCotation($cotation, $cotations[0]);
        }

        return $this->setIndexCotation($cotation, $cotations[0]);
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

        if(empty(get_post_meta($this->id, 'melhorenvio_status_v2'))) {
            return [
                'status' => null,
                'order_id' => null,
                'protocol' => null
            ];
        }

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

    private function translateNameStatus($status = null)
    {
        $statusTranslate = '';
        if ($status == 'pending') {
            $statusTranslate = 'Pendente';
        } elseif ($status == 'released') {
            $statusTranslate = 'Liberado';
        } elseif ($status == 'posted') {
            $statusTranslate = 'Postado';
        } elseif ($status == 'delivered') {
            $statusTranslate = 'Entregue';
        } elseif ($status == 'canceled') {
            $statusTranslate = 'Cancelado';
        } elseif ($status == 'undelivered') {
            $statusTranslate = 'Não entregue';
        } elseif ($status == 'generated') {
            $statusTranslate = 'Gerada';
        } elseif ($status == 'paid') {
            $statusTranslate = 'Paga';
        } else {
            $statusTranslate = 'Não possui';
        }

        return $statusTranslate;
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
        $default = ['number' => null,'key' => null];
        $return = '';
        
        $getPost = get_post_meta($this->id, 'melhorenvio_invoice_v2');
        if(count($getPost) > 0){
            $return = end($getPost);
        } else {
            $return = $default;
        }
       
        return $return;
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
                $data[$order->id]['status']       = $order->status;
                $data[$order->id]['posted_at']    = $order->posted_at;
                $data[$order->id]['delivered_at'] = $order->delivered_at;
            }
            return $data;
        }
        return null;
    }

    public function setIndexCotation($data, $firstCotation)
    {
        $response = [];

        $diff = [];

        foreach ($data as $cot) {
            $cot_id = $cot->id;
            if (is_null($cot_id)) {
                continue;
            }
            $response[$cot_id] =  $cot;

            if ($firstCotation[$cot_id]->price != $cot->price) {

                $diff[$cot_id] = [
                    'first' => str_replace('.', ',', $firstCotation[$cot_id]->price),
                    'last'  => $cot->price,
                    'date'  => date('d/m/Y', strtotime($firstCotation['date_cotation']))
                ];
            }
        }

        $useMelhor = true;
        if (is_null($data['choose_method'])) {

            $useMelhor = false;
            $method = null;
            foreach ($response as $item) {
                if (is_null($item->id)) {
                    continue;
                }
                $method = $item->id;
            }
            $data['choose_method'] = $method;
        }

        if(!array_key_exists(1, $response) && $data['choose_method'] == 1) {
            $data['choose_method'] = 2;
        }

        $response['choose_method'] = $data['choose_method'];
        $response['date_cotation'] = $data['date_cotation'];
        $response['melhorenvio'] = $useMelhor;

        if (isset($data['free_shipping'])) {
            $response['free_shipping'] = $data['free_shipping'];
        }

        $response['diff'] = $diff;

        return $response;
    }
}   
