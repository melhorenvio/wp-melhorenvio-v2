<?php

namespace Services;

class LogService
{
    const ROUTES_NEED_SAVE_RESULT = [
        '/cart', 
        '/shipment/calculate', 
        '/shipment/checkout', 
        '/shipment/generate',
        '/shipment/print'
    ];

    const POST_TYPE_LOG_MELHOR_ENVIO = 'log_melhorenvio';

    public function save($action, $result)
    {   
        if ($this->needSaveIt($action)) {
            
        }
    }

    public function list()
    {

    }

    /**
     * Function to check if need save in logs a return
     *
     * @param string $action
     * @return bool $needSave
     */
    private function needSaveIt($action)
    {
        return (in_array($action, self::ROUTES_NEED_SAVE_RESULT)) ? true : false;
    }
}