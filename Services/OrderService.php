<?php

namespace Services;

class OrderService
{
    const REASON_CANCELED_USER = 2;

    const ROUTE_MELHOR_ENVIO_CANCEL = '/shipment/cancel';

    const ROUTE_MELHOR_ENVIO_CART = '/cart';

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
    public function info($order_id)
    {   
        $data = (new OrderQuotationService())->getData($order_id);

        if(!$data || !isset($data['order_id'])) {
            return [
                'success' => false,
                'message' => 'Ordem não encontrada no Melhor Envio'
            ];
        }

        $result = (new RequestService())->request(
            self::ROUTE_MELHOR_ENVIO_CART . '/' . $data['order_id'],
            'GET',
            [],
            false
        );

        return (array) $result;
    }
}