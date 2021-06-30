<?php

namespace Controllers;

use Services\ClearDataStored;
use Helpers\SessionHelper;

class SessionsController
{
    /**
     * Function to get information from the plugin session
     *
     * @return json
     */
    public function getSession()
    {
        SessionHelper::initIfNotExists();
        
        return wp_send_json($_SESSION, 200);
    }

    /**
     * Function to delete information from the plugin session
     *
     * @return json
     */
    public function deleteSession()
    {
        (new ClearDataStored())->clear();
    }
}
