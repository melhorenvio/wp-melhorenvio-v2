<?php

namespace Helpers;

class DimensionsHelper 
{
    /**
     *  Function to convert the weight of the product to kg if necessary.
     * 
     * @param string $weight
     * @return float
     */
    public function convertWeightUnit($weight) 
    {
        $weight  = (float) $weight;
        $to_unit = strtolower( 'kg' );
        $from_unit = strtolower( get_option( 'woocommerce_weight_unit' ) );

        return floatval(number_format(wc_get_weight( $weight, $to_unit, $from_unit ), 2, '.', ''));
    }

    /**
     * Function that receives the value of the product measurement (width, height or length), and verifies the measurement used in the woocommerce configuration and if the unit is different from cm, converts it to cm, a standard used in API Melhor Envio.
     *
     * @param srting $value
     * @return float
     */
    public function convertUnitDimensionToCentimeter($value)
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

