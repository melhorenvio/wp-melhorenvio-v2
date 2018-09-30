<?php

namespace Controllers;

class TimeController {

    public function setlabel($data) {

        if ($data->max == 1) {
            return ' (1 dia útil)';
        }
        
        if ($data->min == $data->max) {
            return ' (' . $data->max . ' dias úteis)';
        }

        if ( $data->min < $data->max ) {
            return ' ( ' . $data->min . ' à ' . $data->max . ' dias úteis)';
        }

        return '';
    }
}

