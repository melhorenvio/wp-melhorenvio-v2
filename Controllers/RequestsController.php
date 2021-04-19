<?php

namespace Controllers;

use Services\ManageRequestService;

class RequestsController 
{
    /**
     * Controller function to get items in log requests
     * 
     * @return array
     */
    public function getLogs()
    {
        $ordering = 'time';
        if (isset($_GET['ordering']) && in_array($_GET['ordering'], ['time', 'status_code', 'type', 'date'])) {
            $ordering = $_GET['ordering'];
        }

        return wp_send_json([
            'data' => (new ManageRequestService())->get($ordering)
        ], 200);
    }

    public function deleteLogs()
    {
        return wp_send_json([
            'data' => (new ManageRequestService())->deleteAll()
        ], 200);
    }
}
