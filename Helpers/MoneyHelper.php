<?php

namespace Helpers;

use Controllers\ConfigurationController;

class MoneyHelper 
{
    /**
     * @param [type] $data
     * @return void
     */
    public function setlabel($value, $id) 
    {
        $extra = 0;
        $perc  = 0;
        $result = (new ConfigurationController())->getOptionsShipments();

        if (isset($result[$id]['tax'])) {
            $extra = $result[$id]['tax'];
        }

        if (isset($result[$id]['perc'])) {
            $perc = $result[$id]['perc'];
            $perc = ($value / 100) * $perc;
        }

        $value =  floatval($value) + floatval($extra)  + floatval($perc);

        return 'R$' . number_format($value, 2, ',', '.');
    }

    public function setPrice($value, $id) 
    {
        
        $extra = 0;
        $perc  = 0;
        $result = (new ConfigurationController())->getOptionsShipments();

        if (isset($result[$id]['tax'])) {
            $extra = $result[$id]['tax'];
        }

        if (isset($result[$id]['perc'])) {
            $perc = $result[$id]['perc'];
            $perc = ($value / 100) * $perc;
        }
    
        return floatval($value) + floatval($extra)  + floatval($perc) ;
    }
}

