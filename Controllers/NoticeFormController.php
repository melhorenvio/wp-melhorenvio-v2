<?php

namespace Controllers;

use Services\NoticeFormService;

/**
 * Controller responsible for controlling the alert to display the search link about the new plugin
 */
class NoticeFormController
{
    /**
     * Link to search form
     */
    const URL_FORM_MELHOR_ENVIO = 'http://menv.io/pesquisa-wordpress';

    /**
     * Function to open the form link and hide the form alert
     */
    public function openForm()
    {
        $visibility = (new NoticeFormService())->hideForm();
        wp_redirect( self::URL_FORM_MELHOR_ENVIO );
        exit;
    }

    /**
     * Function to show the form alert
     * @return json
     */
    public function showForm()
    {
        $data =  (new NoticeFormService())->showForm();
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
