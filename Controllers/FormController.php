<?php

namespace Controllers;

use Services\FormService;

/**
 * Controller responsible for controlling the alert to display the search link about the new plugin
 */
class FormController
{
    /**
     * Link to search form
     */
    const URL_FORM_MELHOR_ENVIO = 'http://melhorenvio.com';

    /**
     * Function to open the form link and hide the form alert
     */
    public function openForm()
    {
        $visibility = (new FormService())->hideForm();
        wp_redirect( self::URL_FORM_MELHOR_ENVIO );
        exit;
    }

    /**
     * Function to show the form alert
     * @return json
     */
    public function showForm()
    {
        $data =  (new FormService())->showForm();
        return wp_send_json([
            'result' => $data
        ]);
    }

    /**
     * Function to hide the form alert
     * @return json
     */
    public function hideForm()
    {
        $data =  (new FormService())->hideForm();
        return wp_send_json([
            'result' => $data
        ]);
    }
}
