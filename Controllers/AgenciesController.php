<?php

namespace Controllers;

use Services\AgenciesService;

class AgenciesController
{
    /**
     * User selected function to return agencies
     *
     * @return json
     */
    public function get()
    {
        try {
            if (empty($_GET['state'])) {
                return wp_send_json([
                    'message' => 'É necessário informar o estado para reallizar a busca de agências'
                ], 400);
            }

            return wp_send_json(
                (new AgenciesService($_GET))->get(),
                200
            );
        } catch (\Exception $exception) {
            return wp_send_json([
                'success' => false,
                'message' => 'Ocorreu um erro ao obter as agências'
            ], 500);
        }
    }
}
