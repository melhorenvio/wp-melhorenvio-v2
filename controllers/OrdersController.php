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

        $token = get_option('wpmelhorenvio_token');
        $user = new UsersController();
        $package  = new PackageController();
        $products = new ProductsController();

        $body = [
            'from' => $user->getFrom(),
            'to' => $user->getTo($_GET['order_id']),
            'service' => $_GET['choosen'],
            'products' => $products->getProductsOrder($_GET['order_id']),
            'package' => $package->getPackageOrderAfterCotation($_GET['order_id']),
            'options' => [
                "insurance_value" => $products->getInsuranceValue($_GET['order_id']), 
                "receipt" => false,
                "own_hand" => false,
                "collect" => false,
                "reverse" => false, 
                "non_commercial" => false, 
            ]
        ];

        // Caso use transpotadoras, é necessários nota fiscal e chave de nota fiscal.
        if ($_GET['choosen'] >= 3) {

            $invoices = get_post_meta($_GET['order_id'], 'melhorenvio_invoice_v2', true);
            if (!empty($invoices) && $_GET['non_commercial'] != 'true') {
                $body['options']['invoice'] = $invoices;
            }       

            if ($_GET['non_commercial'] == 'true') {
                $body['options']['non_commercial'] = true;
            }
        }

        // Caso use jadlog é necessário informar o ID da agência Jadlog E opção de não comercial
        if ($_GET['choosen'] == 3 || $_GET['choosen'] == 4 ) {
            $body['agency'] = get_option('melhorenvio_agency_jadlog_v2');
        }

        $params = array(
            'headers'           =>  [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ],
            'body'  =>  json_encode($body),
            'timeout'=> 10
        );

        $urlApi = 'https://www.melhorenvio.com.br';
        if(WP_ENV !== null && WP_ENV == 'develop') {
            $urlApi = 'https://sandbox.melhorenvio.com.br';
        } 
        $response =  json_decode(wp_remote_retrieve_body(wp_remote_post($urlApi . 'api/v2/me/cart', $params)));

        if (!isset($response->id)) {
            echo json_encode([
                'success' => false,
                'message' => $response
            ]);
            die;
        }

        $data = [
            'choose_method' => $response->service_id,
            'order_id' => $response->id,
            'protocol' => $response->protocol,
            'status' => 'pending',
            'created' => date('Y-m-d H:i:s')
        ];

        $this->updateDataCotation($_GET['order_id'], $data, 'pending');
        echo json_encode([
            'success' => true,
            'data' => $response
        ]);
        die;
    }

    public function removeOrder() {

        $token = get_option('wpmelhorenvio_token');
        $params = array(
            'headers'           =>  [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ],
            'timeout'=>10,
            'method' => 'DELETE'
        );

        $urlApi = 'https://www.melhorenvio.com.br';
        if(WP_ENV !== null && WP_ENV == 'develop') {
            $urlApi = 'https://sandbox.melhorenvio.com.br';
        } 
        $response =  json_decode(wp_remote_retrieve_body(wp_remote_request($urlApi . '/api/v2/me/cart/' . $_GET['order_id'], $params)));
        if (isset($response->error)) {
            echo json_encode([
                'success' => false,
                'error' => $response->error
            ]);
            die;
        }

        $this->removeDataCotation($_GET['id']);
        echo json_encode([
            'success' => true
        ]);
        die;
    }

    public function cancelOrder() {

        $token = get_option('wpmelhorenvio_token');
        $params = array(
            'headers'           =>  [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ],
            'timeout'=> 10,
            'method' => 'POST',
            'body' => json_encode([
                'id' => $_GET['order_id'],
                'reason_id' => 2,
                'description' => 'Cancelado pelo usuário'
            ])
        );

        $urlApi = 'https://www.melhorenvio.com.br';
        if(WP_ENV !== null && WP_ENV == 'develop') {
            $urlApi = 'https://sandbox.melhorenvio.com.br';
        } 
        $response =  json_decode(wp_remote_retrieve_body(wp_remote_request($urlApi . '/api/v2/me/shipment/cancel', $params)));
        if (isset($response->errors)) {
            echo json_encode([
                'siccess' => false,
                'errors' => $response->errors
            ]);
            die;
        }

        $this->removeDataCotation($_GET['id']);
        echo json_encode([
            'success' => true
        ]);
        die;
    }

    private function removeDataCotation($order_id) {
        delete_post_meta($order_id, 'melhorenvio_status_v2');
    }

    private function getInfoTicket($order_id) {
        $token = get_option('wpmelhorenvio_token');
        $params = array(
            'headers'           =>  [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ],
            'timeout'=>10,
            'method' => 'GET'
        );

        $urlApi = 'https://www.melhorenvio.com.br';
        if(WP_ENV !== null && WP_ENV == 'develop') {
            $urlApi = 'https://sandbox.melhorenvio.com.br';
        } 
        $response =  json_decode(wp_remote_retrieve_body(wp_remote_request($urlApi . '/api/v2/me/cart/' . $order_id, $params)));
        return $response;
    }

    public function insertInvoiceOrder() {

        unset($_GET['action']);
        if (!isset($_GET['id']) || !isset($_GET['number']) || !isset($_GET['key']) ) {
            return json_encode([
                'success' => false,
                'message' => 'Campos ID, number, key são obrigatorios'
            ]);
            die;
        }

        $result = Order::updateInvoice(
            $_GET['id'], 
            [
                'number' => $_GET['number'],
                'key' => $_GET['key']
            ]
        );
        return json_encode($result);
        die;
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

        $token = get_option('wpmelhorenvio_token');
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

        $urlApi = 'https://www.melhorenvio.com.br';
        if(WP_ENV !== null && WP_ENV == 'develop') {
            $urlApi = 'https://sandbox.melhorenvio.com.br';
        } 
        $response =  json_decode(wp_remote_retrieve_body(wp_remote_request($urlApi . '/api/v2/me/shipment/checkout', $params)));
        $data = [
            'order_paid' => $response->purchase->id,
            'protocol_paid' => $response->purchase->protocol,
            'choose_method' => $response->purchase->orders[0]->service_id,
            'order_id' => $response->purchase->orders[0]->id,
            'protocol' => $response->purchase->orders[0]->protocol,
            'status' => 'paid',
        ];

        $this->updateDataCotation($_GET['id'], $data, 'paid');
        echo json_encode([
            'success' => true,
            'data' => $response
        ]);
        die;
    }

    public function createTicket() {
        $body = [
            'orders' => [$_GET['order_id']],
            'mode' => 'public'
        ];

        $token = get_option('wpmelhorenvio_token');
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

        $urlApi = 'https://www.melhorenvio.com.br';
        if(WP_ENV !== null && WP_ENV == 'develop') {
            $urlApi = 'https://sandbox.melhorenvio.com.br';
        } 
        $response =  json_decode(wp_remote_retrieve_body(wp_remote_request($urlApi . '/api/v2/me/shipment/generate', $params)));

        $data = [
            'status' => 'generated',
            'generated_date' => date('Y-m-d H:i:s'),
            'print_order' => null
        ];
        $this->updateDataCotation($_GET['id'], $data, 'generated');

        echo json_encode([
            'success' => true,
            'data' => $response
        ]);
        die;
    }

    public function printTicket() {

        $token = get_option('wpmelhorenvio_token');
        $body = [
            'orders' => [$_GET['order_id']]
        ];

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

        $urlApi = 'https://www.melhorenvio.com.br';
        if(WP_ENV !== null && WP_ENV == 'develop') {
            $urlApi = 'https://sandbox.melhorenvio.com.br';
        } 
        $response =  json_decode(wp_remote_retrieve_body(wp_remote_request($urlApi . '/api/v2/me/shipment/print', $params)));
        $data = [
            'status' => 'printed',
            'printed_date' => date('Y-m-d H:i:s')
        ];
        $this->updateDataCotation($_GET['id'], $data, 'paid');

        echo json_encode([
            'success' => true,
            'data' => $response
        ]);
        die;
    }   

    private function updateDataCotation($order_id, $data, $status) {

        $oldData = end(get_post_meta($order_id, 'melhorenvio_status_v2', true));
        if (empty($oldData || is_null($oldData))) {
            $data = array_merge($oldData, $data);
        }
        
        delete_post_meta($order_id, 'melhorenvio_status_v2');
        add_post_meta($order_id, 'melhorenvio_status_v2', $data);
    }
}
