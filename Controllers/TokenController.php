<?php

namespace Controllers;

use Services\TokenService;

class TokenController
{
    /**
     * Function to return data of user token.
     *
     * @return json
     */
    public function get()
    {
        $tokenData = (new TokenService())->get();
        return wp_send_json($tokenData, 200);
    }

    /**
     * Function to sake data of token
     *
     * @param string $token
     * @param string $token_sandbox
     * @param string $token_environment
     *
     * @return json
     */
    public function save()
    {
        if (!isset($_POST['token'])) {
            return wp_send_json([
                'success' => false,
                'message' => 'Informar o Token'
            ], 400);
        }

        if (!isset($_POST['environment'])) {
            return wp_send_json([
                'success' => false,
                'message' => 'Informar o ambiente'
            ], 400);
        }

        $result = (new TokenService())->save(
            $_POST['token'],
            $_POST['token_sandbox'],
            $_POST['environment']
        );

        if ($result) {
            return wp_send_json([
                'success' => true,
                'message' => 'Token salvo com sucesso'
            ], 200);
        }

        return wp_send_json([
            'success' => false,
            'message' => 'Ocorreu um erro ao salvar o token'
        ], 400);
    }

    /**
     * Function to check exists token instancead
     *
     * @return json
     */
    public function verifyToken()
    {
        if (!get_option('wpmelhorenvio_token')) {
            return wp_send_json([
                'exists_token' => false
            ], 200);
        }
        return wp_send_json([
            'exists_token' => true
        ], 200);
    }
}
