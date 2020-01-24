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
        $show = get_option('melhorenvio_hide_all_jadlog_agencies_product');

        if (!$show) {
            return true;
        }

        if ($show == "1") {
            return false;
        }

        return false;
    }

    /**
     * @param String $value
     * @return bool
     */
    public function set($value) 
    {
        if ($value == 'true') {
            delete_option('melhorenvio_hide_all_jadlog_agencies_product');
            return true;
        } else {
            add_option('melhorenvio_hide_all_jadlog_agencies_product' , 1);
            return false;
        }
    }
}
