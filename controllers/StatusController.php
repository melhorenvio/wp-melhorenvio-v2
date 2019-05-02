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
        echo json_encode(['statusWc' => $status]);
        die;
    }

}

