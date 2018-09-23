<?php

namespace Controllers;

use Models\Order;

class OrdersController {

    public function index() {
        $orders = Order::retrieveMany();
    }

    public function getOrders() {
        unset($_GET['action']);
        $orders = Order::getAllOrders($_GET);
        return json_encode($orders);
    }
}
