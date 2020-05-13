<?php

namespace Services;

class OrderService
{
    const REASON_CANCELED_USER = 2;

    const ROUTE_MELHOR_ENVIO_CANCEL = '/shipment/cancel';

    const ROUTE_MELHOR_ENVIO_TRACKING = '/shipment/tracking';

    const ROUTE_MELHOR_ENVIO_CART = '/cart';

    const ROUTE_MELHOR_ENVIO_CHECKOUT = '/shipment/checkout';

    const ROUTE_MELHOR_ENVIO_CREATE_LABEL = '/shipment/generate';

    const ROUTE_MELHOR_ENVIO_PRINT_LABEL = '/shipment/print';

    const ROUTE_MELHOR_ENVIO_SEARCH = '/orders/search?q=';

    /**
     * Function to cancel order on api Melhor Envio.
     *
     * @param int $post_id
     * @return array $response
     */
    public function cancel($post_id)
    {
        $order_id = $this->getOrderIdByPostId($post_id);

        if (is_null($order_id)) {
            return [
                'success' => false,
                'message' => 'Pedido não encontrado'
            ];
        }

        $orders[] = [
            'id'          => $order_id,
            'reason_id'   => self::REASON_CANCELED_USER,
            'description' => 'Cancelado pelo usuário'
        ];
        
        (new OrderQuotationService())->removeDataQuotation($order_id);
        
        return (new RequestService())->request(
            self::ROUTE_MELHOR_ENVIO_CANCEL, 
            'POST', 
            ['orders' => $orders], 
            false
        );
    }

    /**
     * Function to get info about order in api Melhor Envio.
     *
     * @param int $order_id
     * @return array $response
     */
    public function info($post_id)
    {   
        $data = (new OrderQuotationService())->getData($post_id);

        if(!$data) {
            return [
                'success' => false,
                'message' => 'Ordem não encontrada no Melhor Envio'
            ];
        }

        return (new RequestService())->request(
            self::ROUTE_MELHOR_ENVIO_CART . '/' . $data['order_id'],
            'GET',
            [],
            false
        );
    }


    /**
     * Function to get details about order in api Melhor Envio.
     *
     * @param int $order_id
     * @return array $response
     */
    public function detail($post_id)
    {   
        $data = (new OrderQuotationService())->getData($post_id);

        $body = [
            'orders' => (array) $data['order_id']
        ];

        if(!$data) {
            return [
                'success' => false,
                'message' => 'Ordem não encontrada no Melhor Envio'
            ];
        }
        
        return (new RequestService())->request(
            self::ROUTE_MELHOR_ENVIO_TRACKING,
            'POST',
            $body,
            true
        );
    }

    /**
     * Function to create a label on Melhor Envio.
     *
     * @param array $posts_id
     * @return array $response
     */
    public function pay($posts_id)
    {
        $wallet = 0;
        $orders = [];

        foreach ($posts_id as $post_id) {

            $order_id = $this->getOrderIdByPostId($post_id);

            if (is_null($order_id)) {
                continue;
            }

            $orders[] = $order_id;
            $ticket = $this->infoOrderCart($order_id);
            $wallet = $wallet + $ticket->price;
        }

        if ($wallet == 0) {
            return [
                'success' => false,
                'message' => 'Sem pedidos para pagar'
            ];
        }

        $body = [
            'orders' => $orders,
            'wallet' => $wallet
        ];

        $result = (new RequestService())->request(
            self::ROUTE_MELHOR_ENVIO_CHECKOUT,
            'POST',
            $body,
            true
        );

        return (new OrderQuotationService())->updateDataQuotation(
            end($posts_id), //post_id
            end($result->purchase->orders)->id, //order_id
            end($result->purchase->orders)->protocol, //protocol
            $result->purchase->status, //status
            end($result->purchase->orders)->service_id,//choose_method
            $result->purchase->id //purchase_id
        );
    }

    /**
     * Function to create a label printble on melhor envio.
     *
     * @param int $post_id
     * @return void
     */
    public function createLabel($post_id)
    {
        $order_id = $this->getOrderIdByPostId($post_id);

        $body = [
            'orders' => (array) $order_id,
            'mode' => 'public'
        ];

        $result = (new RequestService())->request(
            self::ROUTE_MELHOR_ENVIO_CREATE_LABEL,
            'POST',
            $body,
            true
        );

        $data = (new OrderQuotationService())->getData($post_id);

        $data = (new OrderQuotationService())->updateDataQuotation(
            $post_id,
            $data['order_id'],
            $data['protocol'],
            'generated',
            $data['choose_method'],
            $data['purchase_id']
        );

        return $data;
    }

    public function printLabel($post_id)
    {
        $order_id = $this->getOrderIdByPostId($post_id);

        $body = [
            'orders' => (array) $order_id
        ];

        $result = (new RequestService())->request(
            self::ROUTE_MELHOR_ENVIO_PRINT_LABEL,
            'POST',
            $body,
            true
        );

        if (!isset($result->url)) {
            return [
                'success' => false,
                'message' => 'Não foi possível imprimir a etiqueta'
            ];
        }

        $data = (new OrderQuotationService())->getData($post_id);

        $data = (new OrderQuotationService())->updateDataQuotation(
            $post_id,
            $data['order_id'],
            $data['protocol'],
            'printed',
            $data['choose_method'],
            $data['purchase_id']
        );

        return $result;
    }

    /**
     * Function to get info about order in api Melhor Envio.
     *
     * @param int $order_id
     * @return array $response
     */
    public function infoOrderCart($order_id)
    {   
        return (new RequestService())->request(
            self::ROUTE_MELHOR_ENVIO_CART . '/' . $order_id,
            'GET',
            [],
            false
        );
    }

    /**
     * Function to get information in Melhor Envio.
     *
     * @param string $order_id
     * @return array $response
     */
    public function getInfoOrder($order_id)
    {
        return (new RequestService())->request(
            self::ROUTE_MELHOR_ENVIO_SEARCH . $order_id,
            'GET',
            [],
            false
        );
    }

    /**
     * Function to get order_id by post_id.
     *
     * @param int $post_id
     * @return string $order_id
     */
    public function getOrderIdByPostId($post_id)
    {
        $data = (new OrderQuotationService())->getData($post_id);

        if (!isset($data['order_id'])) {
            return null;
        }

        return $data['order_id'];
    }
    /**
     * Function to merge status with stauts melhor envio.
     *
     * @param array $posts
     * @return array $response
     */
    public function mergeStatus($posts)
    {
        $response = [];

        foreach ($posts as $post) {

            $status = null;
            $protocol = null;

            $data = (new OrderQuotationService())->getData($post['id']);

            $info = $this->getInfoOrder($data['order_id']);

            if(isset(end($info)->status)) {
                $status   =  end($info)->status;
                $protocol = end($info)->protocol;
            }

            $response[$post['id']] = [
                'order_id' => $data['order_id'],
                'status' => $status,
                'protocol' => $protocol
            ];
        }
        
        return $response;
    }
}