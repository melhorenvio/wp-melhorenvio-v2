<?php

namespace Controllers;

use Controllers\OptionsController;

class TimeController 
{
    /**
     * @param [type] $data
     * @return void
     */
    public function setlabel($data) 
    {
        $timeExtra = (new OptionsController())->get();
        if ($timeExtra['time'] != 0) {
            $data->max = $data->max + $timeExtra['time'];
            $data->min = $data->min + $timeExtra['time'];
        }

        if ($data->max == 1) {
            return ' (1 dia útil)';
        }
        
        if ($data->min == $data->max) {
            return ' (' . $data->max . ' dias úteis)';
        }

        if ( $data->min < $data->max ) {
            return ' ( ' . $data->min . ' a ' . $data->max . ' dias úteis)';
        }

        return '';
    }
}

