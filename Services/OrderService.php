<?php

namespace Services;

class OrderService
{
    const REASON_CANCELED_USER = 2;

    const ROUTE_MELHOR_ENVIO_CANCEL = '/shipment/cancel';

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
}