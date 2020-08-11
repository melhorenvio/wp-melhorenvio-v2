<?php

namespace Controllers;

use Services\SessionService;

class SessionsController
{
    /**
     * Function to get information from the plugin session
     *
     * @return json
     */
    public function getSession()
    {
        return wp_send_json($_SESSION, 200);
    }

    /**
     * Function to delete the plugin session
     *
     * @return json
     */
    public function deleteSession()
    {
        return wp_send_json((new SessionService())->delete(), 200);
    }
}
