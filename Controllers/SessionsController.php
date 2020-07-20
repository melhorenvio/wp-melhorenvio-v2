<?php

namespace Controllers;

use Services\SessionService;

class SessionsController 
{
    /**
     * Function to get information from the plugin session
     *
     * @return array
     */
    public function getSession()
    {
        echo json_encode($_SESSION);
        die;
    }

    /**
     * Function to delete the plugin session
     *
     * @return void
     */
    public function deleteSession()
    {
        echo json_encode((new SessionService())->delete());
        die;
    }
}
