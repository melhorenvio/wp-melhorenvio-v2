<?php

namespace Helpers;

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

    /**
     * Function to converter price to floatval
     * 
     * @param string $price
     * @return float
     */
    public static function converterPriceToFloat($value)
    {
        if (is_string($value)) {

            $value = preg_replace("/[^0-9,.]/", "", $value);
            
            $value = trim($value);
    
            if (preg_match('/^\d*\.\d+\,\d+/', $value)) {
                $value = str_replace('.', '', $value);
            } elseif (preg_match('/^\d*\,\d+\.\d+/', $value)) {
                $value = str_replace(',', '', $value);
            }
    
            return (float) str_replace(',', '.', $value);
        }
    
        return $value;
    }
}
