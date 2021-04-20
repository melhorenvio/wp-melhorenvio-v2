<?php

namespace Services;

class ManageRequestService
{
    const WP_OPTIONS_REQUEST_LOGS = 'melhorenvio_requests_logs';

    public function register($route, $statusCode, $type, $params, $response, $time)
    {
        $requestLogs = get_option(self::WP_OPTIONS_REQUEST_LOGS, []);

        $requestLogs[] = [
            'route' => $route,
            'type' => $type,
            'status_code' => $statusCode,
            'time' => $time,
            'params' => $params,
            'response' => $response,
            'date' => date('Y-m-d H:i:s')
        ];

        update_option(self::WP_OPTIONS_REQUEST_LOGS, $requestLogs);
    }

    public function get($ordering)
    {
        if (empty($ordering))  {
            $ordering = 'time';
        }

        return $this->filterRegisters(get_option(self::WP_OPTIONS_REQUEST_LOGS, []), $ordering);
    }

    public function deleteAll()
    {
        return  delete_option(self::WP_OPTIONS_REQUEST_LOGS);
    }

    public function filterRegisters($requests, $ordering)
    {   
        if (empty($requests)) {
            return $requests;
        }

        $dateLimit = date('Y-m-d',strtotime('-1 months'));

        foreach ($requests as $key => $request) {
            $dateLog = date('Y-m-d', strtotime($request['date']));
            if ($dateLog < $dateLimit) {
                unset($requests[$key]);
            }            
        }

        update_option(self::WP_OPTIONS_REQUEST_LOGS, $requests);

        usort($requests, function($a, $b) use ($ordering) {
            return $a[$ordering] > $b[$ordering];
        });

        return $requests;

    }

}
