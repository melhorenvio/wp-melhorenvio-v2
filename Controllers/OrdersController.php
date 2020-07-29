<?php

namespace Controllers;

use Models\Order;
use Services\OrdersProductsService;
use Services\BuyerService;
use Services\CartService;
use Services\OrderService;
use Services\OrderQuotationService;
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
        return wp_send_json($orders, 200);
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
        return wp_send_json($data, 200);
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
            return wp_send_json([
                'success' => false,
                'message' => 'Informar o ID do pedido'
            ], 412);
        }

        if (!isset($_GET['choosen'])) {
            return wp_send_json([
                'success' => false,
                'message' => 'Informar o ID do serviço selecionado'
            ], 412);
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
                return wp_send_json([
                    'success' => false,
                    'errors' => $result['errors'],
                ], 400);
            }

            (new OrderQuotationService())->removeDataQuotation($_GET['order_id']);

            return wp_send_json([
                'success' => false,
                'errors' => (array) 'Ocorreu um erro ao envio o pedido para o carrinho de compras do Melhor Envio.',
            ], 400);
        }

        $result = (new OrderService())->payByOrderId($_GET['order_id'], $result['order_id']);

        if (!isset($result['order_id'])) {

            if (isset($result['errors'])) {
                return wp_send_json([
                    'success' => false,
                    'errors' => $result['errors']
                ], 400);
            }

            return wp_send_json([
                'success' => false,
                'message' => (array) 'Ocorreu um erro ao pagar o pedido no Melhor Envio.',
                'result' => $result
            ], 400);
        }

        $result = (new OrderService())->createLabel($_GET['order_id']);

        return wp_send_json([
            'success' => true,
            'message' => (array) 'Pedido gerado com sucesso',
            'data' => $result
        ], 200);
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
            return wp_send_json([
                'success' => false,
                'message' => 'Informar o ID do pedido'
            ], 400);
        }

        $result = (new CartService())->remove($_GET['id']);

        return wp_send_json([
            'success' => true,
            'message' => 'Pedido removido do carrinho de compras'
        ], 200);
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
            return wp_send_json([
                'success' => false,
                'message' => 'Informar o ID do pedido'
            ], 400);
        }

        $result = (new OrderService())->cancel($_GET['id']);

        return wp_send_json([
            'success' => true,
            'message' => 'Pedido cancelado'
        ], 200);
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
            return wp_send_json([
                'success' => false,
                'message' => 'Ocorreu um erro ao realizar o pagamento'
            ], 400);
        }

        return wp_send_json([
            'success' => true,
            'message' => 'Pedido pago',
            'data' => $result
        ], 200);
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

        return wp_send_json([
            'success' => true,
            'message' => 'Pedido gerado',
            'data' => $result
        ], 200);
    }

    /**
     * @return void
     */
    public function printTicket()
    {
        $createResult = (new OrderService())->createLabel($_GET['id']);

        if (!isset($createResult['status'])) {
            return wp_send_json([
                'success' => false,
                'message' => 'Ocorreu um erro ao gerar a etiqueta'
            ], 400);
        }

        $result = (new OrderService())->printLabel($_GET['id']);

        return wp_send_json([
            'success' => true,
            'message' => 'Pedido impresso',
            'data' => $result
        ], 200);
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
            return wp_send_json([
                'success' => false,
                'message' => 'Informar o IDs dos pedidos'
            ], 400);
        }

        $result = (new OrderService())->buyOnClick($_GET['ids']);

        if (isset($result['url'])) {
            return wp_send_json([
                'success' => true,
                'errors' => $result['errors'],
                'url' => $result['url']
            ], 200);
        }

        return wp_send_json([
            'success' => false,
            'errors' => $result['errors']
        ], 400);
    }

    /**
     * @return void
     */
    public function insertInvoiceOrder()
    {
        unset($_GET['action']);

        if (!isset($_GET['id']) || !isset($_GET['number']) || !isset($_GET['key'])) {
            return json_encode([
                'success' => false,
                'message' => 'Campos ID, number, key são obrigatorios'
            ], 400);
        }

        $result = Order::updateInvoice(
            $_GET['id'],
            [
                'number' => $_GET['number'],
                'key' => $_GET['key']
            ]
        );

        return json_encode($result);
    }
}
