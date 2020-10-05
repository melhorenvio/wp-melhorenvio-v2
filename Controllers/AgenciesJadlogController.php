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
     * @param string city
     * @param string state
     * @return json
     */
    public function getByAddress($city, $state)
    {
        try {
            $agencies = (new AgenciesJadlogService())->getByAddress($city, $state);

            if (empty($agencies)) {
                return wp_send_json([
                    'success' => false,
                    'message' => sprintf(
                        "Não foram encontradas agências Jadlog para a cidade %s/%s",
                        $city,
                        $state
                    )
                ], 404);
            }

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

    /**
     * function to get agencies jadlog by state
     *
     * @param string state
     * @return json
     */
    public function getByState($state)
    {
        try {
            $agencies = (new AgenciesJadlogService())->getByState($state);
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

    /**
     * function to get agencies jadlog states user
     *
     * @return json
     */
    public function getByStateUser()
    {
        try {
            $agencies = (new AgenciesJadlogService())->getByStateUser();
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
