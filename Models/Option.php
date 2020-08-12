<?php

namespace Models;

class Option 
{
    /**
     * @return void
     */
    public function get() 
    {
        $options = get_option('melhorenvio_options');

        if (!$options) {
            return [
                'tax' => 0,
                'time' => 0
            ];
        }

        return $options;
    }

    /**
     * Function for receiving quote options (AR and MP)
     *
     * @return void
     */
    public function getOptions()
    {
        $ar = get_option('melhorenvio_ar');
        $mp = get_option('melhorenvio_mp');


        return (object) array( 
            'ar' => filter_var($ar, FILTER_VALIDATE_BOOLEAN),
            'mp' => filter_var($mp, FILTER_VALIDATE_BOOLEAN)
        );
    }

    /**
    * @param [type] $options
    * @return void
    */
    public function save($options) 
    {
        $data = [
            'tax' => floatval($options['tax']),
            'time' => intval($options['time'])
        ];

        delete_option('melhorenvio_options');
        add_option('melhorenvio_options', $data);

        return [
            'success' => true,
            'tax' => $data['tax'],
            'time' => $data['time']
        ];
    }
}
