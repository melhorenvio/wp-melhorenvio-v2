<?php

namespace Controllers;

use Models\Token;

class TokenController
{
    /**
     * Function to return data of user token.
     *
     * @return json
     */
    public function getToken()
    {
        $codeStore = md5(get_option('home'));

        if (isset($_SESSION[$codeStore]['melhorenvio_token'])) {
            return wp_send_json([
                'token' => $_SESSION[$codeStore]['melhorenvio_token'],
                'token_sandbox' => $_SESSION[$codeStore]['melhorenvio_token_sandbox'],
                'token_environment' => $_SESSION[$codeStore]['melhorenvio_token_environment']
            ], 200);
        }

        if (
            isset($_SESSION[$codeStore]['melhorenvio_token']) && !is_null($_SESSION[$codeStore]['melhorenvio_token']) &&
            isset($_SESSION[$codeStore]['melhorenvio_token_sandbox']) && !is_null($_SESSION[$codeStore]['melhorenvio_token_sandbox']) &&
            isset($_SESSION[$codeStore]['melhorenvio_token_environment']) && !is_null($_SESSION[$codeStore]['melhorenvio_token_environment'])
        ) {
            return wp_send_json([
                'token' => $_SESSION[$codeStore]['melhorenvio_token'],
                'token_sandbox' => $_SESSION[$codeStore]['melhorenvio_token_sandbox'],
                'environment' => $_SESSION[$codeStore]['melhorenvio_token_environment']
            ], 200);
        }

        $_SESSION[$codeStore]['melhorenvio_token'] = (new token())->getToken();

        return wp_send_json([
            'token' => $_SESSION[$codeStore]['melhorenvio_token']['token'],
            'token_sandbox' => $_SESSION[$codeStore]['melhorenvio_token']['token_sandbox'],
            'environment' => $_SESSION[$codeStore]['melhorenvio_token']['token_environment']
        ], 200);
    }

    public function token()
    {
        $codeStore = md5(get_option('home'));

        if (isset($_SESSION[$codeStore]['melhorenvio_token']) && !is_null($_SESSION[$codeStore]['melhorenvio_token'])) {
            return $_SESSION[$codeStore]['melhorenvio_token'];
        }

        $token = (new token())->getToken();

        if (!$token) {
            return false;
        }

        $_SESSION[$codeStore]['melhorenvio_token'] = $token;

        return $token;
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
    public function saveToken()
    {
        $codeStore = md5(get_option('home'));

        unset($_SESSION[$codeStore]);

        if (!isset($_POST['token'])) {
            return wp_send_json([
                'success' => false,
                'message' => 'Informar o Token'
            ], 400);
        }

        $result = (new Token())->saveToken($_POST['token'], $_POST['token_sandbox'], $_POST['environment']);

        $_SESSION[$codeStore]['melhorenvio_token']['token']         = $_POST['token'];
        $_SESSION[$codeStore]['melhorenvio_token']['token_sandbox'] = $_POST['token_sandbox'];
        $_SESSION[$codeStore]['melhorenvio_token']['environment']   = $_POST['environment'];

        return wp_send_json([
            'success' => $result
        ], 200);
    }

    /**
     * Function to check exists token instancead
     *
     * @return json
     */
    public function verifyToken()
    {
        if (!get_option('wpmelhorenvio_token')) {
            return wp_send_json(['exists_token' => false]);
            die;
        }
        return wp_send_json(['exists_token' => true], 200);
    }
}
