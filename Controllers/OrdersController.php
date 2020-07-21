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
use Services\ShippingMelhorEnvioService;
use Services\ListOrderService;

class OrdersController 
{
    /**
     * @return void
     */
    public function getOrders() 
    {
        unset($_GET['action']);
        $orders = (new ListOrderService())->getList($_GET);
        echo json_encode($orders);
        die();
    }

    /**
     * Function to search for an order quote.
     *
     * @param int $id
     * @return array
     */
    public function getOrderQuotationByOrderId($id)
    {
        $data = (new OrderQuotationService())->getQuotation($id);
        echo json_encode($data);die;
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

        $products = (new OrdersProductsService())->getProductsOrder($_GET['order_id']);

        $buyer = (new BuyerService())->getDataBuyerByOrderId($_GET['order_id']);

        $result = (new CartService())->add(
            $_GET['order_id'], 
            $products, 
            $buyer, 
            $_GET['choosen']
        );

        if (!isset($result['order_id'])) {

            if (isset($result['errors'])) {
                echo json_encode([
                    'success' => false,
                    'errors' => $result['errors'],
                ]);die;
            }

            (new OrderQuotationService())->removeDataQuotation($_GET['order_id']);

            echo json_encode([
                'success' => false,
                'errors' => (array) 'Ocorreu um erro ao envio o pedido para o carrinho de compras do Melhor Envio.',
            ]);die;
        }

        $result = (new OrderService())->payByOrderId($_GET['order_id'], $result['order_id']);

        if (!isset($result['order_id'])) {

            if (isset($result['errors'])) {
                echo json_encode([
                    'success' => false,
                    'errors' => $result['errors']
                ]);die;
            }

            echo json_encode([
                'success' => false,
                'message' => (array) 'Ocorreu um erro ao pagar o pedido no Melhor Envio.',
                'result' => $result
            ]);die;
        }

        $result = (new OrderService())->createLabel($_GET['order_id']);

        echo json_encode([
            'success' => true,
            'message' => (array) 'Pedido gerado com sucesso',
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

        if (!isset($result['purchase_id'])) {
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

    /**
     * Function to make a step by step to printed any labels
     *
     * @param _GET $ids     
     * @return array $response;
     */
    public function buyOnClick()
    {
        if (!isset($_GET['ids'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Informar o IDs dos pedidos'
            ]);
            die;
        }

        $result = (new OrderService())->buyOnClick($_GET['ids']);

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
