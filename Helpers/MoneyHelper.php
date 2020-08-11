<?php

namespace Helpers;

use Services\OptionsMethodShippingService;

class MoneyHelper
{
    /**
     * Function to define the customized price
     *
     * @param string $value
     * @param string $extra
     * @return string
     */
    public static function price($value, $extra)
    {
        return 'R$' . number_format((floatval($value) + floatval($extra)), 2, ',', '.');
    }

    /**
     * Function to define the price
     *
     * @param string $value
     * @param string $extra
     * @return float
     */
    public static function cost($value, $extra)
    {
        return floatval($value) + floatval($extra);
    }
}
