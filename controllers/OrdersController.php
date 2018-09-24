<?php

namespace Controllers;

use Models\Order;
use Controllers\UsersController;
use Controllers\PackageController;
use Controllers\ProductsController;

class OrdersController {

    public function index() {
        $orders = Order::retrieveMany();
    }

    public function getOrders() {
        unset($_GET['action']);
        $orders = Order::getAllOrders($_GET);
        return json_encode($orders);
    }

    public function sendOrder() {

        $token = get_option('melhorenvio_token');
        $user = new UsersController();

        $package = new PackageController();
        $products = new ProductsController();

        $body = [
            'from' => $user->getFrom(),
            'to' => $user->getTo($_GET['order_id']),
            'service' => $_GET['choosen'],
            'agency' => null,
            'products' => $products->getProductsOrder($_GET['order_id']),
            'package' => $package->getPackageOrder($_GET['order_id']),
            'options' => [
                "insurance_value" => $products->getInsuranceValue($_GET['order_id']), 
                "receipt" => false,
                "own_hand" => false,
                "collect" => false,
                "reverse" => false, 
                "non_commercial" => false, 
                "invoice" => [
                    "number" => null, 
                    "key" => null 
                ]
            ]
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

        $response =  json_decode(wp_remote_retrieve_body(wp_remote_post('https://www.melhorenvio.com.br/api/v2/me/cart', $params)));

        // TODO verificar os error de retorno
        if ($response->error) {
            echo json_encode([
                'error' => true,
                'message' => $response->error
            ]);
            die;
        }

        $this->updateDataCotation($_GET['order_id'], $response, 'pending');
        echo json_encode([
            'success' => true,
            'data' => $response
        ]);
        die;
    }

    public function removeOrder() {

        $token = get_option('melhorenvio_token');
        $params = array(
            'headers'           =>  [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ],
            'timeout'=>10,
            'method' => 'DELETE'
        );

        $response =  json_decode(wp_remote_retrieve_body(wp_remote_request('https://www.melhorenvio.com.br/api/v2/me/cart/' . $_GET['order_id'], $params)));
        
        $this->removeDataCotation($_GET['id']);

        echo json_encode([
            'success' => true
        ]);
    }

    private function updateDataCotation($order_id, $data, $status) {
        
        $data = [
            'choose_method' => $data->service_id,
            'order_id' => $data->id,
            'protocol' => $data->protocol,
            'status' => $status,
            'created' => date('Y-m-d H:i:s')
        ];

        add_post_meta($order_id, 'melhorenvio_status_v2', $data);
    }

    private function removeDataCotation($order_id) {
        delete_post_meta($order_id, 'melhorenvio_status_v2');
    }

    public function payTicket() {

        $ticket = $this->getInfoTicket($_GET['order_id']);
        if($ticket->status != 'pending') {
            echo json_encode([
                'success' => false,
                'error' => 'Impossivel pagar etiqueta com status ' . $ticket->status
            ]);
            die;
        }

        $body = [
            'orders' => [$_GET['order_id']],
            'wallet' => $ticket->price
        ];

        $token = get_option('melhorenvio_token');
        $params = array(
            'headers'           =>  [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ],
            'body' => json_encode($body),
            'timeout'=> 10,
            'method' => 'POST'
        );

        $response =  json_decode(wp_remote_retrieve_body(wp_remote_request('https://www.melhorenvio.com.br/api/v2/me/shipment/checkout', $params)));
        $data = [
            'order_paid' => $response->purchase->id,
            'protocol_paid' => $response->purchase->protocol,
            'choose_method' => $response->orders[0]->service_id,
            'order_id' => $response->orders[0]->id,
            'protocol' => $response->orders[0]->protocol,
            'status' => 'paid',
        ];

        $this->updateDataCotation($_GET['id'], $data, 'paid');
        echo json_encode([
            'success' => true,
            'data' => $response
        ]);
        die;
    }

    private function getInfoTicket($order_id) {
        $token = get_option('melhorenvio_token');
        $params = array(
            'headers'           =>  [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ],
            'timeout'=>10,
            'method' => 'GET'
        );
        $response =  json_decode(wp_remote_retrieve_body(wp_remote_request('https://www.melhorenvio.com.br/api/v2/me/cart/' . $order_id, $params)));
        return $response;
    }
}
