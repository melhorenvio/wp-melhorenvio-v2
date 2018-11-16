<?php

namespace Controllers;

use Controllers\ConfigurationController;

class MoneyController 
{
    /**
     * @param [type] $data
     * @return void
     */
    public function setlabel($value, $id) 
    {
        $extra = 0;
        $result = (new ConfigurationController())->getOptionsShipments();

        if (isset($result[$id]['tax'])) {
            $extra = $result[$id]['tax'];
        }
    
        if ($extra != 0) {
            $value = floatval($value) + floatval($extra);
        }

        return 'R$' . number_format($value, 2, ',', '.');
    }

    public function setPrice($value, $id) 
    {
        $extra = 0;
        $result = (new ConfigurationController())->getOptionsShipments();

        if (isset($result[$id]['tax'])) {
            $extra = $result[$id]['tax'];
        }
    
        if ($extra != 0) {
            $value = floatval($value) + floatval($extra);
        }

        return $value;
    }
}

