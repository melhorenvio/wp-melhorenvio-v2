<?php

namespace Controllers;

use Models\Order;

class OrdersController {

    public function index() {
        $order = (new Order(749))->retrieveOne();
        var_dump($order);
        die;
    }

    public function getOrders() {
        $order = new Order();
        $orders = $order->retrieveMany();

        echo json_encode($orders);
        die;
    }
}

