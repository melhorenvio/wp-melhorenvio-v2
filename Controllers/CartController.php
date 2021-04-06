<?php

namespace Controllers;

use Services\CartService;

class CartController 
{
    /**
     * Controller function to get items in the shopping cart
     * 
     * @return array
     */
    public function getInfoCart()
    {
        $data = (new CartService())->getInfoCart();

        return $data;
    }
}
