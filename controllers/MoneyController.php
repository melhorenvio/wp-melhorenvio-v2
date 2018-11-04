<?php

namespace Controllers;

use Controllers\OptionsController;

class MoneyController 
{
    /**
     * @param [type] $data
     * @return void
     */
    public function setlabel($value) 
    {
        $extra = (new OptionsController())->get();
        if ($extra['tax'] != 0) {
            $value = floatval($value) + floatval($extra['tax']);
        }

        return 'R$' . number_format($value, 2, ',', '.');
    }

    public function setPrice($value) 
    {
        $extra = (new OptionsController())->get();
        if ($extra['tax'] != 0) {
            $value = floatval($value) + floatval($extra['tax']);
        }

        return $value;
    }
}

