<?php

namespace Controllers;

use Controllers\ConfigurationController;
use Controllers\OptionsController;

class TimeController 
{
    /**
     * @param [type] $data
     * @return void
     */
    public function setlabel($data, $id, $custom = null) 
    {
<<<<<<< HEAD
=======
        if (!is_null($custom) && $data->min != $custom->min && $data->max != $custom->min ) {
            
            if ($data->max == 1) {
                return ' (1 dia útil)';
            }
            
            if ($data->min == $data->max) {
                return ' (' . $data->max . ' dias úteis)';
            }
    
            if ( $data->min < $data->max ) {
                return ' ( ' . $data->min . ' a ' . $data->max . ' dias úteis)';
            }
    
            return $data->max . ' dias úteis';
        }


>>>>>>> master
        if (is_null($data)) {
            return '*';
        }

        $timeExtra = 0;
        $extra = (new ConfigurationController())->getOptionsShipments();

        if (isset($extra[$id]['time'])) {
            $timeExtra = $extra[$id]['time'];
        }
    
        if ($timeExtra != 0) {
            $data->max = $data->max + $timeExtra;
            $data->min = $data->min + $timeExtra;
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

        return $data->max . ' dias úteis';
    }
}

