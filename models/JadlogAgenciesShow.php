<?php

namespace Models;

use Models\Address;

class JadlogAgenciesShow
{
    /**
     * @return bool
     */
    public function get() 
    {
        $show = get_option('melhorenvio_show_all_jadlog_agencies');

        if (!$show) {
            return false;
        }

        if ($show == "1" || filter_var($show, FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }

        return false;
    }

    /**
     * @param String $value
     * @return bool
     */
    public function set($value) 
    {
        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);

        if ($value) {
            add_option('melhorenvio_show_all_jadlog_agencies' , 1);
        } else {
            delete_option('melhorenvio_show_all_jadlog_agencies');
        }

        return $value;
    }
}
