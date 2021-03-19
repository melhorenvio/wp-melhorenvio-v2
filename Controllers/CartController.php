<?php

namespace Controllers;

use Services\CartService;

class CartController 
{
    public function getInfoCart()
    {
        $data = (new CartService())->getInfoCart();

        return $data;
    }
}
