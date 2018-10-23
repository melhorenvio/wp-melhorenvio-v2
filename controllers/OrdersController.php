<?php

namespace Controllers;

use Models\Order;
use Controllers\UsersController;
use Controllers\PackageController;
use Controllers\ProductsController;
use Controllers\LogsController;

class OrdersController 
{
    const URL = 'https://www.melhorenvio.com.br';

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

        $token = get_option('wpmelhorenvio_token');

        $body = [
            'from' => (new UsersController())->getFrom(),
            'to' => (new UsersController())->getTo($_GET['order_id']),
            'service' => $_GET['choosen'],
            'products' => (new ProductsController())->getProductsOrder($_GET['order_id']),
            'package' => (new PackageController())->getPackageOrderAfterCotation($_GET['order_id']),
            'options' => [
                "insurance_value" => (new ProductsController())->getInsuranceValue($_GET['order_id']), 
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

        if (is_null($body['to']->postal_code)) {
            echo json_encode([
                'success' => false,
                'message' => 'Falta campo CEP do destino'
            ]);
            die;
        }

        if (is_null($body['from']->postal_code)) {
            echo json_encode([
                'success' => false,
                'message' => 'Falta campo CEP da origem'
            ]);
            die;
        }

        if (is_null($body['package'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Embalagem é obrigatório'
            ]);
            die;
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

        $response =  json_decode(
            wp_remote_retrieve_body(
                wp_remote_post(self::URL . '/api/v2/me/cart', $params)
            )
        );

        $logs = (new LogsController)->add(
            $_GET['order_id'], 
            'Enviando ordem', 
            $params, 
            $response, 
            'OrdersController', 
            'sendOrder', 
            self::URL . '/api/v2/me/cart'
        );

        if (!isset($response->id)) {
            $error = $this->normalizeErrors($response, $_GET['order_id'], 'sendOrder');
            echo json_encode([
                'success' => false,
                'message' => $error
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

        $response =  json_decode(wp_remote_retrieve_body(wp_remote_request(self::URL . '/api/v2/me/cart/' . $_GET['order_id'], $params)));

        (new LogsController)->add(
            $_GET['id'], 
            'Removendo do carrinho', 
            $params, 
            $response, 
            'OrdersController', 
            'removeOrder', 
            self::URL . '/api/v2/me/cart'
        );

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

    /**
     * @return boolean
     */
    public function cancelOrder() 
    {
        $token = get_option('wpmelhorenvio_token');

        $params = array(
            'headers'           =>  [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ],
            'timeout'=> 10,
            'method' => 'POST',
            'body' => json_encode(['order' => [
                'id' => $_GET['order_id'],
                'reason_id' => 2,
                'description' => 'Cancelado pelo usuário'
            ]])
        );

        $response =  json_decode(
            wp_remote_retrieve_body(
                wp_remote_request(self::URL . '/api/v2/me/shipment/cancel', $params)
            )
        );

        (new LogsController)->add(
            $_GET['id'], 
            'Cancelando do carrinho', 
            $params, 
            $response, 
            'OrdersController', 
            'cancelOrder', 
            self::URL . '/api/v2/me/shipment/cancel'
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

        return json_decode(wp_remote_retrieve_body(wp_remote_request(self::URL . '/api/v2/me/cart/' . $order_id, $params)));
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

        $response =  json_decode(
            wp_remote_retrieve_body(
                wp_remote_request(self::URL . '/api/v2/me/shipment/checkout', $params)
            )
        );

        (new LogsController)->add(
            $_GET['id'], 
            'Pagando etiqueta', 
            $params, 
            $response, 
            'OrdersController', 
            'payTicket', 
            self::URL . '/api/v2/me/shipment/checkout'
        );

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

    /**
     * @return void
     */
    public function createTicket() 
    {
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

        $response =  json_decode(
            wp_remote_retrieve_body(
                wp_remote_request(self::URL . '/api/v2/me/shipment/generate', $params)
            )
        );

        (new LogsController)->add(
            $_GET['id'], 
            'Criando etiqueta', 
            $params, 
            $response, 
            'OrdersController', 
            'createTicket', 
            self::URL . '/api/v2/me/shipment/generate'
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

        $response =  json_decode(
            wp_remote_retrieve_body(
                wp_remote_request(self::URL . '/api/v2/me/shipment/print', $params)
            )
        );

        (new LogsController)->add(
            $_GET['id'], 
            'Imprimindo etiqueta', 
            $params, 
            $response, 
            'OrdersController', 
            'printTicket', 
            self::URL . '/api/v2/me/shipment/print'
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
        $oldData = end(get_post_meta($order_id, 'melhorenvio_status_v2', true));
        if (empty($oldData || is_null($oldData))) {
            $data = array_merge($oldData, $data);
        }
        
        delete_post_meta($order_id, 'melhorenvio_status_v2');
        add_post_meta($order_id, 'melhorenvio_status_v2', $data);
    }

    /**
     * @param [type] $data
     * @param [type] $order_id
     * @param [type] $action
     * @return void
     */
    private function normalizeErrors($data, $order_id = null, $action = null) 
    {

        if (!is_null($order_id)) {
            (new LogsController)->add($order_id, '[OrdersController] (normalizeErrors)', [], $data);
        }

        if (isset($data->message) && !isset($data->errors)) {
            return $data->message;
        }

        if (isset($data->error) && isset($data->message)) {
            return $data->error;
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
