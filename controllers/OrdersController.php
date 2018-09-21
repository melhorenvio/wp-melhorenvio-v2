<?php

namespace Controllers;

use Models\Order;

class OrdersController {

    public function index() {
        $orders = Order::retrieveMany();
    }

    public function getOrders() {
        
        $orders = Order::getAllOrders();
        return json_encode($orders);
    }
}
