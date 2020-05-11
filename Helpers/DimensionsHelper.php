<?php

namespace Helpers;

class DimensionsHelper 
{
    /**
     * @param [type] $weight
     * @return void
     */
    public function converterIfNecessary($weight) 
    {
        $weight  = (float) $weight;
        $to_unit = strtolower( 'kg' );
    
        
        $from_unit = strtolower( get_option( 'woocommerce_weight_unit' ) );
        
        // Unify all units to kg first.
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
    
        return ( $weight < 0 ) ? 0 : $weight;
    }

    public function converterDimension($value)
    {
        $unit = get_option('woocommerce_dimension_unit');
        if ($unit == 'mm') {
            return $value / 10;
        }

        if ($unit == 'm') {
            return $value * 10;
        }

        return $value;
    }
}

