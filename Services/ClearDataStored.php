<?php

namespace Services;

use Models\Seller;
use Models\ShippingService;

class ClearDataStored
{
    /**
     * Function to clear data about seller stored in session or database.
     *
     * @return void
     */
    public function clear()
    {
        (new Seller())->destroy();
        (new ShippingService())->destroy();
    }
}
