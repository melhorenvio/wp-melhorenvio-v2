<?php

namespace Controllers;

use Services\ClearDataStored;

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
     * Function to delete information from the plugin session
     *
     * @return json
     */
    public function deleteSession()
    {
        (new ClearDataStored())->clear();
    }
}
