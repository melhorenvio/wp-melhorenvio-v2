<?php

namespace Controllers;

use Models\Order;
use Models\Log;
use Models\Method;
use Controllers\UsersController;
use Controllers\PackageController;
use Controllers\ProductsController;
use Controllers\LogsController;
use Controllers\TokenController;
use Services\QuotationService;
use Services\OrdersProductsService;
use Services\BuyerService;
use Services\CartService;
use Services\OrderService;
use Services\OrderQuotationService;

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
     * Function to add order in cart Melhor Envio.
     * 
     * @param GET order_id
     * @param GET choosen
     * @return json $results
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

        if (!isset($_GET['choosen']) || !in_array($_GET['choosen'], Method::SERVICES_CODE_MELHOR_ENVIO)) {
            echo json_encode([
                'success' => false,
                'message' => 'Verificar o código do serviço'
            ]);die;
        }

        $products = (new OrdersProductsService())->getProductsOrder($_GET['order_id']);

        $buyer = (new BuyerService())->getDataBuyerByOrderId($_GET['order_id']);

        $result = (new CartService())->add(
            $_GET['order_id'], 
            $products, 
            $buyer, 
            $_GET['choosen']
        );

        if (!isset($result['order_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Ocorreu um erro ao envio o pedido para o carrinho de compras do Melhor Envio.'
            ]);die;
        }

        echo json_encode([
            'success' => true,
            'data' => $result
        ]);die;
    }

    /**
     * Function to remove order on cart Melhor Envio.
     * 
     * @param GET $order_id
     * @return json $response
     */
    public function removeOrder() 
    {
        if (!isset($_GET['order_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Informar o ID do pedido'
            ]);
            die;
        }

        $result = (new CartService())->remove($_GET['id']);

        echo json_encode([
            'success' => true
        ]);
        die;
    }

    /**
     * Function to cancel orderm on api Melhor Envio.
     * 
     * @param GET $order_id
     * @return array $response
     */
    public function cancelOrder() 
    {
        if (!isset($_GET['order_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Informar o ID do pedido'
            ]);
            die;
        }

        $result = (new OrderService())->remove(
            explode(',', $_GET['order_id'])
        );

        echo json_encode([
            'success' => true
        ]);
        die;
    }

    /**
     * Function to pay a order Melhor Envio.
     * 
     * @param GET $order_id
     * @return array $response
     */
    public function payTicket() 
    {
        $posts = explode(',', $_GET['id']);

        $result = (new OrderService())->pay($posts);

        if (!isset($result['purchase_id']) || is_null($result['purchase_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Ocorreu um erro ao realizar o pagamento'
            ]);
            die; 
        }

        echo json_encode([
            'success' => true,
            'data' => $result
        ]);
        die; 
    }

    /**
     * Function to get info about order on cart Melhor Envio.
     * 
     * @param int $order_id
     * @return void
     */
    private function getInfoTicket() 
    {
        if (!isset($_GET['order_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Informar o ID do pedido'
            ]);
            die;
        }

        $result = (new RequestService())->request(
            '/cart/' . $_GET['order_id'],
            'GET',
            [],
            false
        );

        return json_decode($result);
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

    //TODO Refatorei essa função em OrderQuotationService.
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
