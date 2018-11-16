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
