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
     * @param string $percent
     * @return string
     */
    public static function price($value, $extra, $percent)
    {
        $value = floatval($value);
        $extra = floatval($extra);
        $percent = floatval($percent);

        $value = self::calculateFinalValue($value, $extra, $percent);

        return 'R$' . number_format($value, 2, ',', '.');
    }

    /**
     * Function to define the price
     *
     * @param string $value
     * @param string $extra
     * @param string $percent
     * @return float
     */
    public static function cost($value, $extra, $percent)
    {
        $value = floatval($value);
        $extra = floatval($extra);
        $percent = floatval($percent);

        return self::calculateFinalValue($value, $extra, $percent);
    }

    /**
     * Function to calculate final value
     *
     * @param float $value
     * @param float $extra
     * @param float $percent
     * @return string
     */
    public static function calculateFinalValue($value, $extra, $percent)
    {

        $percentExtra = ($value / 100) * $percent;

        return $value + $percentExtra + $extra;
    }
}
