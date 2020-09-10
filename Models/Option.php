<?php

namespace Models;

class Option 
{
    const OPTION_RECEIPT = 'melhorenvio_ar';

    const OPTION_OWN_HAND = 'melhorenvio_mp';

    const OPTION_INSURANCE_VALUE = 'melhorenvio_vs';

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
        $receipt = get_option(self::OPTION_RECEIPT);
        $own_hand = get_option(self::OPTION_OWN_HAND);
        $insurance_value = get_option(self::OPTION_INSURANCE_VALUE);


        return (object) array( 
            'receipt' => filter_var($receipt, FILTER_VALIDATE_BOOLEAN),
            'own_hand' => filter_var($own_hand, FILTER_VALIDATE_BOOLEAN),
            'insurance_value' => filter_var($insurance_value, FILTER_VALIDATE_BOOLEAN)
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
