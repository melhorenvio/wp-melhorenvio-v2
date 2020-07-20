<?php

namespace Helpers;

class DimensionsHelper 
{
    /**
     * @param string $weight
     * @return void
     */
    public function convertWeightUnit($weight) 
    {
        $weight  = (float) $weight;
        $to_unit = strtolower( 'kg' );
        $from_unit = strtolower( get_option( 'woocommerce_weight_unit' ) );

        return floatval(number_format(wc_get_weight( $weight, $to_unit, $from_unit ), 2, '.', ''));
    }

    public function converterDimension($value)
    {
        $unit = get_option('woocommerce_dimension_unit');
        if ($unit == 'mm') {
            $value = $value / 10;
        }

        if ($unit == 'm') {
            $value = $value * 10;
        }

        return number_format($value, 2, '.', '');
    }
}

