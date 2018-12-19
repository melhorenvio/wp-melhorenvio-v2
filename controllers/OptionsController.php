<?php

namespace Controllers;

use Models\Option;

class OptionsController 
{
    /**
     * @return void
     */
    public function getName($id, $method, $company) 
    {

        global $wpdb;

        $sql = sprintf("select * from %soptions where option_name = 'melhor_envio_option_method_shipment_%s'", $wpdb->prefix, (String) $id);

        $results = $wpdb->get_results($sql);

        if (!$results) {
            return [
                'method' => $method,
                'company' => $company
            ];
        }

        $data = $results[0];
        $data = unserialize($data->option_value);

        if (!isset($data['name']) || $data['name'] == "" || $data['name'] == 'undefined') {
            return [
                'method' => $method,
                'company' => $company
            ];
        }

        return [
            'method' => $data['name'],
            'company' => ''
        ];
    }

}

