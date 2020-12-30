<?php

namespace Controllers;

use Services\AgenciesAzulService;

class AgenciesAzulController
{
    /**
     * User selected function to return azul agency
     *
     * @return json
     */
    public function get()
    {
        try {
            return wp_send_json(
                (new AgenciesAzulService())->get(),
                200
            );
        } catch (\Exception $exception) {
            return wp_send_json([
                'success' => false, 'message' => 'Ocorreu um erro ao obter as agências da Azul Cargo Express (Error code 1)'
            ], 500);
        }
    }

    /**
     * function to get agencies Azul Cargo Express by city and state
     *
     * @param string city
     * @param string state
     * @return json
     */
    public function getByAddress($city, $state)
    {
        try {
            $agencies = (new AgenciesAzulService())->getByAddress($city, $state);

            if (empty($agencies)) {
                return wp_send_json([
                    'success' => false,
                    'message' => sprintf(
                        "Não foram encontradas agências Azul Cargo Express para a cidade %s/%s",
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
                'success' => false, 'message' => 'Ocorreu um erro ao obter as agências da Azul Cargo Express (Error code 2)'
            ], 500);
        }
    }

    /**
     * function to get agencies Azul Cargo Express by state
     *
     * @param string state
     * @return json
     */
    public function getByState($state)
    {
        try {
            $agencies = (new AgenciesAzulService())->getByState($state);
            return wp_send_json([
                'success' => true,
                'agencies' => $agencies
            ], 200);
        } catch (\Exception $exception) {
            return wp_send_json([
                'success' => false, 'message' => 'Ocorreu um erro ao obter as agências da Azul Cargo Express (Error code 3)'
            ], 500);
        }
    }

    /**
     * function to get agencies Azul Cargo Express states user
     *
     * @return json
     */
    public function getByStateUser()
    {
        try {
            $agencies = (new AgenciesAzulService())->getByStateUser();
            return wp_send_json([
                'success' => true,
                'agencies' => $agencies
            ], 200);
        } catch (\Exception $exception) {
            return wp_send_json([
                'success' => false, 'message' => 'Ocorreu um erro ao obter as agências da Azul Cargo Express (Error code 4)'
            ], 500);
        }
    }
}
