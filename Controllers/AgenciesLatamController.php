<?php

namespace Controllers;

use Services\AgenciesLatamService;

class AgencieslatamController
{
    /**
     * User selected function to return latam agency
     *
     * @return json
     */
    public function get()
    {
        try {
            return wp_send_json(
                (new AgenciesLatamService())->get(),
                200
            );
        } catch (\Exception $exception) {
            return wp_send_json([
                'success' => false, 'message' => 'Ocorreu um erro ao obter as unidades da Latam Cargo (Error code 1)'
            ], 500);
        }
    }

    /**
     * function to get agencies Latam by city and state
     *
     * @param string city
     * @param string state
     * @return json
     */
    public function getByAddress($city, $state)
    {
        try {
            $agencies = (new AgenciesLatamService())->getByAddress($city, $state);

            if (empty($agencies)) {
                return wp_send_json([
                    'success' => false,
                    'message' => sprintf(
                        "Não foram encontradas unidades Latam para a cidade %s/%s",
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
                'success' => false, 'message' => 'Ocorreu um erro ao obter as unidades da Latam (Error code 2)'
            ], 500);
        }
    }

    /**
     * function to get agencies Latam by state
     *
     * @param string state
     * @return json
     */
    public function getByState($state)
    {
        try {
            $agencies = (new AgenciesLatamService())->getByState($state);
            return wp_send_json([
                'success' => true,
                'agencies' => $agencies
            ], 200);
        } catch (\Exception $exception) {
            return wp_send_json([
                'success' => false, 'message' => 'Ocorreu um erro ao obter as unidades da Latam (Error code 3)'
            ], 500);
        }
    }

    /**
     * function to get agencies Latam states user
     *
     * @return json
     */
    public function getByStateUser()
    {
        try {
            $agencies = (new AgenciesLatamService())->getByStateUser();
            return wp_send_json([
                'success' => true,
                'agencies' => $agencies
            ], 200);
        } catch (\Exception $exception) {
            return wp_send_json([
                'success' => false, 'message' => 'Ocorreu um erro ao obter as unidades da Latam (Error code 4)'
            ], 500);
        }
    }
}
