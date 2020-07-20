<?php

namespace Helpers;

class DimensionsHelper 
{
    /**
     * @param [type] $weight
     * @return void
     */
    public function convertWeightUnit($weight) 
    {
        $weight  = (float) $weight;
        $to_unit = strtolower( 'kg' );
        $from_unit = strtolower( get_option( 'woocommerce_weight_unit' ) );
        
        if ( $from_unit !== $to_unit ) {
            switch ( $from_unit ) {
                case 'g':
                $weight *= 0.001;
                break;
                case 'lbs':
                $weight *= 0.453592;
                break;
                case 'oz':
                $weight *= 0.0283495;
                break;
            }
    
          // Output desired unit.
            switch ( $to_unit ) {
                case 'g':
                $weight *= 1000;
                break;
                case 'lbs':
                $weight *= 2.20462;
                break;
                case 'oz':
                $weight *= 35.274;
                break;
            }
        }

        return number_format((( $weight < 0 ) ? 0 : $weight), 2, '.', '');
    }

    public function converterDimension($value)
    {
        $unit = get_option('woocommerce_dimension_unit');
        if ($unit == 'mm') {
            return number_format(($value / 10), 2, '.', '');
        }

        if ($unit == 'm') {
            return number_format(($value * 10), 2, '.', '');
        }

        return number_format($value, 2, '.', '');
    }
}

