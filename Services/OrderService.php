<?php

namespace Services;

class OrderService
{
    const REASON_CANCELED_USER = 2;

    const ROUTE_MELHOR_ENVIO_CANCEL = '/shipment/cancel';

    const ROUTE_MELHOR_ENVIO_CART = '/cart';

    const ROUTE_MELHOR_ENVIO_CHECKOUT = '/shipment/checkout';

    /**
     * Function to cancel order on api Melhor Envio.
     *
     * @param array $ordersIds
     * @return array $response
     */
    public function cancel($ordersIds)
    {
        $orders = [];

        foreach ($ordersIds as $order) {

            $orders[] = [
                'id'          => $order,
                'reason_id'   => self::REASON_CANCELED_USER,
                'description' => 'Cancelado pelo usuário'
            ];
        }

        foreach ($orders as $order) {
            (new OrderQuotationService())->removeDataQuotation($order);
        }

        return (new RequestService())->request(
            self::ROUTE_MELHOR_ENVIO_CANCEL, 
            'POST', 
            ['orders' => $orders ], 
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
     * Function to get info about order in api Melhor Envio.
     *
     * @param int $order_id
     * @return array $response
     */
    public function infoOrderId($order_id)
    {   
        return (new RequestService())->request(
            self::ROUTE_MELHOR_ENVIO_CART . '/' . $order_id,
            'GET',
            [],
            false
        );
    }

    /**
     * Function to get order_id by post_id
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
            $ticket = $this->infoOrderId($order_id);
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
            end($posts_id),
            end($result->purchase->orders)->id,
            end($result->purchase->orders)->protocol,
            $result->purchase->status,
            end($result->purchase->orders)->service_id,
            $result->purchase->id
        );
    }
}