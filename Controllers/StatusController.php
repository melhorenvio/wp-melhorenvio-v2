<?php

namespace Controllers;

class StatusController 
{
    /**
     * @return void
     */
    public function getStatus() 
    {
        $status = wc_get_order_statuses();
        return wp_send_json(['statusWc' => $status], 200);
    }

}

