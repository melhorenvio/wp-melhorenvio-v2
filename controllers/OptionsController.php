<?php

namespace Controllers;

use Models\Option;

class OptionsController 
{
    /**
     * @return void
     */
    public function get() 
    {
        return (new Option)->get();
    }

    /**
     * @return void
     */
    public function getJson() 
    {
        echo json_encode((new Option)->get());
        die;
    }


    /**
     * @return void
     */
    public function save() 
    {
        $errors = [];

        if (!isset($_GET['tax'])) {
            $errors[] = 'Informar o parametro "tax"';
        }

        if (!isset($_GET['time'])) {
            $errors[] = 'Informar o parametro "time"';
        }

        if (!empty($errors)) {
            echo json_encode([
                'success' => false,
                'massage' => $errors
            ]);
            die;
        }

        $result = (new Option)->save($_GET);

        echo json_encode($result);die;
    }
}

