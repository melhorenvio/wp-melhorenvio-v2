<?php

namespace Models;

use Helpers\TranslateStatusHelper;
use Services\QuotationService;
use Services\OrderQuotationService;
use Services\RequestService;

class Order {
    
    const URL = 'https://api.melhorenvio.com';

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

                $products = $order->getProducts();

                $statusTranslate = (new TranslateStatusHelper())->translateNameStatus($dataMelhorEnvio['status']);

                $non_commercial = true;
                if (!is_null($invoice['number']) && !is_null($invoice['key']) ) {
                    $non_commercial = false;
                }
                
                if (!is_null($dataMelhorEnvio['order_id'])) {
                    $orders[] = $dataMelhorEnvio['order_id'];
                }
                
                $data[] = [
                    'id'             => (int) $order->id,
                    'products'       => (Object) $products,
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

    private function mountPackage($cotation)
    {
        $response = null;

        if (empty($cotation) || is_null($cotation)) {
            return $response;
        }
        foreach($cotation as $item){

            if(!isset($item->id) || is_null($item->id)) {
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

    /**
     * @param [type] $posts
     * @param [type] $orders
     * @return void
     */
    private function matchStatus($posts, $orders) 
    {
        $statusApi = $this->getStatusApi($orders);   

        foreach ($posts as $key => $post) {

            if (isset($post['order_id'])) {

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

        return (new OrderQuotationService())->getQuotation($this->id);
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
        
        $data = (new OrderQuotationService())->getData($this->id);

        if(empty($data) || count($data) == 0) {
            return [
                'status' => null,
                'order_id' => null,
                'protocol' => null
            ];
        }

        $status = $data['status'];
        $protocol = $data['protocol'];
        $order_id = $data['order_id'];
        
        if (!$data) {
            $status = $this->getOldstatus($this->id);
        }

        $default = [
            'status' => $status,
            'order_id' => $order_id,
            'protocol' => $protocol
        ];

        if (empty($data) || !$data) {
            return $default;
        }

        if (!is_array($data['protocol'])) {
            $data['protocol'] = $data['protocol'];
        }

        if (!is_array($data['order_id'])) {
            $data['order_id'] = $data['order_id'];
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
        $return = '';
        if ($id) $this->id = $id; 
        $default = ['number' => null, 'key' => null ];

        $getPost = get_post_meta($this->id, 'melhorenvio_invoice_v2');
        if(count($getPost) > 0) {
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

        $body = [
            "orders" => $arrayOrders
        ];

        $response = (new RequestService())->request(
            self::ROUTE_MELHOR_ENVIO_TRACKING,
            'POST',
            $body,
            true
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
}   
