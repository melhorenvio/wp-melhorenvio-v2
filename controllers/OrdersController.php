<?php

namespace Controllers;

use Models\Order;
use Models\Log;
use Models\Method;
use Services\QuotationService;
use Services\OrdersProductsService;
use Services\BuyerService;
use Services\CartService;
use Services\OrderService;
use Services\OrderQuotationService;

class OrdersController 
{
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
            'message' => 'Pedido enviado para o carrinho de compras',
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
            'success' => true,
            'message' => 'Pedido removido do carrinho de compras'
        ]);
        die;
    }

    /**
     * Function to cancel orderm on api Melhor Envio.
     * 
     * @param GET $post_id
     * @return array $response
     */
    public function cancelOrder() 
    {
        if (!isset($_GET['id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Informar o ID do pedido'
            ]);
            die;
        }

        $result = (new OrderService())->cancel($_GET['id']);

        echo json_encode([
            'success' => true,
            'message' => 'Pedido cancelado'
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
            'message' => 'Pedido pago',
            'data' => $result
        ]);
        die; 
    }

    /**
     * Function to create a label on Melhor Envio.
     * 
     * @param GET $post_id
     * @return array $response
     */
    public function createTicket() 
    {
        $result = (new OrderService())->createLabel($_GET['id']);

        echo json_encode([
            'success' => true,
            'message' => 'Pedido gerado',
            'data' => $result
        ]);
        die; 
    }

    /**
     * @return void
     */
    public function printTicket() 
    {
        $createResult = (new OrderService())->createLabel($_GET['id']);

        if (!isset($createResult['status'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Ocorreu um erro ao gerar a etiqueta'
            ]); 
            die;
        }

        $result = (new OrderService())->printLabel($_GET['id']);

        echo json_encode([
            'success' => true,
            'message' => 'Pedido impresso',
            'data' => $result
        ]);
        die; 
    }  

    public function buyOnClick()
    {
        if (!isset($_GET['ids'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Informar o IDs dos pedidos'
            ]);
            die;
        }

        $ids = explode(",", $_GET['ids']);

        $result = (new OrderService())->buyOnClick($ids);

        if (isset($result['url'])) {
            echo json_encode([
                'success' => true,
                'errors' => $result['errors'],
                'url' => $result['url']

            ]);die;
        }

        echo json_encode([
            'success' => false,
            'errors' => $result['errors']
        ]);die;
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
}
