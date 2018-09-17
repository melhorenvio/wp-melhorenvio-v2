<?php

namespace Models;

use Bases\bOrders;

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
    public static function retrieveMany(Array $filters = NULL) : Array
    {
        $args = array(
            'numberposts' => ($filters['limit']) ?: 10,
            'per_page' => ($filters['skip']) ?: 0,
            'post_status' => 'public',
            'post_type' => 'shop_order'
        );

        $posts =  get_posts($args);
        $data = [];
        foreach ($posts as $post) {

            $order = new Order($post->ID);
            $data[] =  [
                'id' => $order->id,
                'total' => $order->total,
                'products' => $order->getProducts(),
                'cotation' => $order->getCotation(),
                'address' => $order->address,
                'to' => $order->to
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

        $cotation = get_post_meta($this->id, 'melhorenvio_cotation', true);
        if (!$cotation or empty($cotation)) {
            $this->makeCotation();
        }
        return $cotation;
    }

    /**
     * Make cotation order on API Melhor Envio.
     *
     * @param [Int] $id
     * @return object
     */
    public function makeCotation() {

        $package = get_post_meta($this->id, 'melhorenvio_package', true);

        if(!$package) {

            $token = get_option('melhorenvio_token');
            $body = [
                "from" => [
                    "postal_code" => '96065710'
                ],
                'to' => [
                    'postal_code' => str_replace('-', '', $this->address['postcode'])
                ],
                'products' => $this->products,
                'options' => [
                    "insurance_value" => $this->total,
                    "receipt"         => false, 
                    "own_hand"        => false, 
                    "collect"         => false 
                ],
                "services" => '1,2,3,4,5,7'
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

            $response =  json_decode(wp_remote_retrieve_body(wp_remote_post('https://www.melhorenvio.com.br/api/v2/me/shipment/calculate', $params)));

            $package = [
                'width'  => $response->packages[0]->dimensions->width,
                'height' => $response->packages[0]->dimensions->height,
                'length' => $response->packages[0]->dimensions->length,
                'weigth' => $response->packages[0]->weight
            ];

            add_post_meta($this->id, 'melhorenvio_package', $package);
            add_post_meta($this->id, 'melhorenvio_cotation', $response);

            return $package;
        }

        return $package;
    }

    function normalizeToArray($data) {
        $result = [];
        foreach ($data as $item) {
            $packages = [];
            if (!empty($item->packages)) {
                foreach ($item->packages as $pack) { 
                    $products = [];
                    foreach ($pack->products as $product) {
                        $products[] = [
                            'id' => $product->id,
                            'quantity' => $product->quantity
                        ];
                    }
                    $packages[] = [
                        'price' => $pack->price,
                        'discount' => $pack->discount,
                        'format' => $pack->format,
                        'dimensions' => [
                            'height' => $pack->dimensions->height,
                            'width' => $pack->dimensions->width,
                            'length' => $pack->dimensions->length
                        ],
                        'weight' => $pack->weight,
                        'insurance_value' => $pack->insurance_value,
                        'products' => $products
                    ];
                }
            }
    
            $result[] = [
                'id' => $item->id,
                'name' => $item->name,
                'price' => $item->price,
                'delivery_time' => $item->delivery_time,
                'currency' => $item->currency,
                'delivery_range' => [
                    'min' => $item->delivery_range->min,
                    'max' => $item->delivery_range->max
                ],
                'packages' => $packages,
                'additional_services' => [
                    'receipt' => $item->additional_services->receipt,
                    'own_hand' => $item->additional_services->own_hand,
                    'collect' => $item->additional_services->collect
                ],
                'company' => [
                    'id' => $item->company->id,
                    'name' => $item->company->name
                ],
                'selected' => $item->selected,
                // 'postcode' => $postcode,
                // 'postcode_client' => $postcodeClient
            ];
        }
        return $result;
    }
    
}   