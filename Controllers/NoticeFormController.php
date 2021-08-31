<?php

namespace Controllers;

use Services\NoticeFormService;

class NoticeFormController
{
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
