<?php

namespace Controllers;

use Models\Order;
use Models\Log;
use Controllers\UsersController;
use Controllers\PackageController;
use Controllers\ProductsController;
use Controllers\LogsController;
use Controllers\TokenController;

class OrdersController 
{
    const URL = 'https://api.melhorenvio.com';

    public function get($id)
    {
        return Order::getOne($id);
    }

    /**
     * @return void
     */
    public function getOrders() 
    {
        unset($_GET['action']);
        $orders = Order::getAllOrders($_GET);
        return json_encode($orders);
    }

    /**
     * @return void
     */
    public function sendOrder() 
    {
        if (!isset($_GET['order_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Informar o ID do pedido'
            ]);
            die;
        }

        if (!isset($_GET['choosen'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Informar o ID do serviço selecionado'
            ]);
            die;
        }

        $token = (new tokenController())->token();

        $products = (new ProductsController())->getProductsOrder($_GET['order_id']);

        $packages = (new PackageController())->getPackageOrderAfterCotation($_GET['order_id']);

        if (empty($packages)) {
            echo json_encode([
                'success' => false,
                'message' => 'O pacote está vazio'
            ]);die;
        }

        foreach ($packages[$_GET['choosen']][0] as $key => $attribute) {
            if (is_null($attribute)) {
                echo json_encode([
                    'success' => false,
                    'message' => printf('Por favor, informar o valor para %s', $key)
                ]);die;
            }
        }

        if (!isset($_GET['choosen']) || !in_array($_GET['choosen'], [1,2,3,4,5,6,7,8,9,10,11])) {
            echo json_encode([
                'success' => false,
                'message' => 'Verificar o código do serviço'
            ]);die;
        }

        $from = (new UsersController())->getFrom();

        if ($_GET['choosen'] == 3 || $_GET['choosen'] == 4) {
            if(is_null($from->phone)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Por favor, informar seu telefone no seu cadastro'
                ]);
                die;
            }

            if (!get_option('melhorenvio_agency_jadlog_v2')) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Por favor, selecionar uma agência Jadlog no painel de configurações'
                ]);
                die;
            }
        }

        $to = (new UsersController())->getTo($_GET['order_id']);

        if (is_null($to->postal_code)) {
            echo json_encode([
                'success' => false,
                'message' => 'Falta campo CEP do destino'
            ]);
            die;
        }

        if (is_null($from->postal_code)) {
            echo json_encode([
                'success' => false,
                'message' => 'Falta campo CEP da origem'
            ]);
            die;
        }

        $errors = [];
        $success = [];
        $orders_id = [];
        $protocols = [];

        foreach ($packages[$_GET['choosen']] as $package) {

            $insurance_value = 0;
            foreach ($products as $key => $item) {
                unset($products[$key]['insurance_value']);
                $insurance_value = $insurance_value + ($item['quantity'] * $item['unitary_value'] );
            }
            
            unset($package['insurnace_value']);

            $reminder = null;
            if (count($packages[$_GET['choosen']]) > 1) {
                $reminder = sprintf('Volume %s/%s - %s itens', $package['volume'], count($packages[$_GET['choosen']]), $package['quantity']);
            }

            $body = array(
                'from' => $from,
                'to' => $to,
                'service' => $_GET['choosen'],
                'products' => $products,
                'package' => $package,
                'options' => array(
                    "insurance_value" => round($insurance_value, 2), 
                    "receipt" => (get_option('melhorenvio_ar') == 'true') ? true : false,
                    "own_hand" => (get_option('melhorenvio_mp') == 'true') ? true : false,
                    "collect" => false,
                    "reverse" => false, 
                    "non_commercial" => false, 
                    'platform' => 'WooCommerce V2',
                    'reminder' => $reminder
                )
            );

            // Caso use jadlog é necessário informar o ID da agência Jadlog E opção de não comercial
            if ($_GET['choosen'] == 3 || $_GET['choosen'] == 4 ) {
                $body['agency'] = get_option('melhorenvio_agency_jadlog_v2'); 
                if(is_null($body['agency']) || $body['agency'] == "null" ) {
                    echo json_encode([
                        'success' => false,
                        'message' => printf('Para utilizar o serviço da Jadlog é necessário informar o ID da agência')
                    ]);
                    die;
                }

                if (is_null($body['to']->phone) || empty($body['to']->phone)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Telefone do destinatario é obrigatorio para serviços da jadLog'
                    ]);
                    die;
                }
            }

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

            $params = array(
                'headers'           =>  array(
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                    'Authorization' => 'Bearer '.$token,
                ),
                'body'   =>  json_encode($body),
                'timeout'=> 10
            );

            $response = json_decode(
                wp_remote_retrieve_body(
                    wp_remote_post(self::URL . '/v2/me/cart', $params)
                )
            );

            delete_post_meta($_GET['order_id'], 'melhorenvio_errors');
            $logErrors = array();
            if (isset($response->errors)) {
                foreach ($response->errors as $key => $items) {
                    foreach ($items as $item) {
                        $logErrors[$_GET['choosen']][] = [
                            'message' =>  $item
                        ];
                    }
                }
            }

            // save erros
            if (empty($logErrors)) {
                delete_post_meta($_GET['order_id'], 'melhorenvio_errors');
            } else {
                add_post_meta($_GET['order_id'], 'melhorenvio_errors', $logErrors);
            }

            (new Log())->register($_GET['order_id'], 'send_order', $body, $response);

            if (!isset($response->id)) {
                $er = $this->normalizeErrors($response, $_GET['order_id'], 'sendOrder');
                if ($er != false) {
                    $errors[] = $er;
                    continue;
                }
            }

            $success[] = $response;

            $orders_id[] = $response->id;

            $protocols[] = $response->protocol;   
        }

        if (empty($success) || empty($orders_id) || empty($protocols)) {
            echo json_encode([
                'success' => false,
                'message' => 'Ocorreu um erro'
            ]);die;
        }

        $data['choose_method'] = $_GET['choosen'];
        $data['status'] = 'pending';
        $data['created'] = date('Y-m-d H:i:s');
        $data['order_id'] = $orders_id;
        $data['protocol'] = $protocols;

        $this->updateDataCotation($_GET['order_id'], $data, 'pending');

        if (empty($errors)) {
            echo json_encode([
                'success' => true,
                'data' => $data
            ]);die;
        }

        echo json_encode([
            'success' => false,
            'message' => $errors
        ]);die;
    }

    /**
     * @return void
     */
    public function removeOrder() 
    {
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

        $orders = explode(',', $_GET['order_id']);

        $errors = [];
        $success = [];

        foreach ($orders as $order) {

            $response =  json_decode(wp_remote_retrieve_body(wp_remote_request(self::URL . '/v2/me/cart/' . $order, $params)));

            if (isset($response->error)) {
                $errors[] = $response->error;
                continue;
            }

            (new LogsController)->add(
                $_GET['id'], 
                'Removendo do carrinho', 
                $params, 
                $response, 
                'OrdersController', 
                'removeOrder', 
                self::URL . '/v2/me/cart'
            );
        }

        if (!empty($errors)) {
            echo json_encode([
                'success' => false,
                'error' => end($errors)
            ]);
            die;
        }

        $this->removeDataCotation($_GET['id']);
        echo json_encode([
            'success' => true
        ]);
        die;
    }

    /**
     * @return boolean
     */
    public function cancelOrder() 
    {
        $ordersIds = explode(',', $_GET['order_id']);
        
        $orders = [];

        foreach ($ordersIds as $order) {
            $orders[] = [
                'id' => $order,
                'reason_id' => 2,
                'description' => 'Cancelado pelo usuário'
            ];
        }

        $token = get_option('wpmelhorenvio_token');

        $params = array(
            'headers'           =>  [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ],
            'timeout'=> 10,
            'method' => 'POST',
            'body' => ['orders' => $orders]
        );

        $response =  json_decode(
            wp_remote_retrieve_body(
                wp_remote_request(self::URL . '/v2/me/shipment/cancel', $params)
            )
        );

        (new LogsController)->add(
            $_GET['id'], 
            'Cancelando do carrinho', 
            $params, 
            $response, 
            'OrdersController', 
            'cancelOrder', 
            self::URL . '/v2/me/shipment/cancel'
        );

        if (isset($response->errors)) {
            echo json_encode([
                'success' => false,
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

    /**
     * @param [type] $order_id
     * @return void
     */
    private function removeDataCotation($order_id) 
    {
        delete_post_meta($order_id, 'melhorenvio_status_v2');
    }

    /**
     * @param [type] $order_id
     * @return void
     */
    private function getInfoTicket($order_id) 
    {
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

        return json_decode(wp_remote_retrieve_body(wp_remote_request(self::URL . '/v2/me/cart/' . $order_id, $params)));
    }

    /**
     * @return void
     */
    public function insertInvoiceOrder() 
    {
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

    /**
     * @return void
     */
    public function payTicket() 
    {
        $orders = explode(',', $_GET['order_id']);

        $wallet = 0;
        foreach ($orders as $order) {
            $ticket = $this->getInfoTicket($order);
            $wallet = $wallet + $ticket->price;
        }

        $body = [
            'orders' => $orders,
            'wallet' => $wallet
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

        $response =  json_decode(
            wp_remote_retrieve_body(
                wp_remote_request(self::URL . '/v2/me/shipment/checkout', $params)
            )
        );

        (new LogsController)->add(
            $_GET['id'], 
            'Pagando etiqueta', 
            $params, 
            $response, 
            'OrdersController', 
            'payTicket', 
            self::URL . '/v2/me/shipment/checkout'
        );

        if(isset($response->error)) {
            echo json_encode([
                'success' => false,
                'data' => $response->error
            ]);
            die;
        }

        $data = [
            'order_paid' => $response->purchase->id,
            'protocol_paid' => $response->purchase->protocol,
            'choose_method' => $response->purchase->orders[0]->service_id,
            'order_id' => $orders,
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

    /**
     * @return void
     */
    public function createTicket() 
    {
        $orders = explode(',', $_GET['order_id']);

        $body = [
            'orders' => $orders,
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

        $response =  json_decode(
            wp_remote_retrieve_body(
                wp_remote_request(self::URL . '/v2/me/shipment/generate', $params)
            )
        );

        (new LogsController)->add(
            $_GET['id'], 
            'Criando etiqueta', 
            $params, 
            $response, 
            'OrdersController', 
            'createTicket', 
            self::URL . '/v2/me/shipment/generate'
        );

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

    /**
     * @return void
     */
    public function printTicket() 
    {
        $token = get_option('wpmelhorenvio_token');

        $orders = explode(',', $_GET['order_id']);

        $body = [
            'orders' => $orders
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

        $response =  json_decode(
            wp_remote_retrieve_body(
                wp_remote_request(self::URL . '/v2/me/shipment/print', $params)
            )
        );

        (new LogsController)->add(
            $_GET['id'], 
            'Imprimindo etiqueta', 
            $params, 
            $response, 
            'OrdersController', 
            'printTicket', 
            self::URL . '/v2/me/shipment/print'
        );

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

    /**
     * @param [type] $order_id
     * @param [type] $data
     * @param [type] $status
     * @return void
     */
    private function updateDataCotation($order_id, $data, $status) 
    {
        $newData = [];

        $newData['choose_method'] = $data['choose_method'];
        $newData['protocol'] = $data['protocol'];
        $newData['order_id'] = $data['order_id'];
        $newData['status'] = $status;
        $newData['created'] = date('Y-m-d H:i:s');
        
        delete_post_meta($order_id, 'melhorenvio_status_v2');
        add_post_meta($order_id, 'melhorenvio_status_v2', $newData);
    }

    /**
     * @param [type] $data
     * @param [type] $order_id
     * @param [type] $action
     * @return void
     */
    private function normalizeErrors($data, $order_id = null, $action = null) 
    {
        if (is_null($data)) {
            return $false;
        }

        if (!is_null($order_id)) {
            (new LogsController)->add($order_id, '[OrdersController] (normalizeErrors)', [], $data);
        }

        if (isset($data->message) && !isset($data->errors)) {
            return $data->message;
        }

        if (isset($data->error) && isset($data->message)) {
            return $data->error;
        }

        if (isset($data->agency)) {
            return 'Agência Jadlog invalida';
        }
        
        if (isset($data->errors)) {
            foreach($data->errors as $key => $error) {
                if (end($error) == 'validation.nfe') {
                    return 'Chave da Nota fiscal inválida';
                }

                if (end($error) == 'The options.invoice.number may not be greater than 12 characters.') {
                    return 'A nota fiscal deve conter 12 digitos';
                }

                return end($error);
            }
            return $data->errors;
        }

        if (isset($data->error)) {
            return $data->error;
        }

        return 'Ocorreu um erro';
    }
}
