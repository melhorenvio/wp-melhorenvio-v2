<?php

namespace Controllers;

use Models\Order;

class OrdersController {

    public function index($id = null) {
        $order = (new Order($id))->retrieveOne();
        var_dump($order);
        die;
    }

    public function getOrders() {
        $order = new Order();
        unset($_GET['action']);

        $orders = $order->retrieveMany($_GET);
        echo json_encode($orders);
        die;
    }
}

