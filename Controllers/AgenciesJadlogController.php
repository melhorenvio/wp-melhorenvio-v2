<?php

namespace Controllers;

use Services\AgenciesJadlogService;

class AgenciesJadlogController
{
    /**
     * User selected function to return jadlog agency
     *
     * @return json
     */
    public function get()
    {
        try {
            return wp_send_json(
                (new AgenciesJadlogService())->get(),
                200
            );
        } catch (\Exception $exception) {
            return wp_send_json([
                'success' => false, 'message' => 'Ocorreu um erro ao obter as agências'
            ], 400);
        }
    }

    /**
     * function to get agencies jadlog by city and state
     *
     * @return json
     */
    public function getByAddress($city, $state)
    {
        try {
            $agencies = (new AgenciesJadlogService())->getByAddress($city, $state);
            return wp_send_json([
                'success' => true,
                'agencies' => $agencies
            ], 200);
        } catch (\Exception $exception) {
            return wp_send_json([
                'success' => false, 'message' => 'Ocorreu um erro ao obter as agências'
            ], 400);
        }
    }
}
