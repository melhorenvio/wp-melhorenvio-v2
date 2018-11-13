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

}

