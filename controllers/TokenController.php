<?php

namespace Controllers;

use Models\Token;

class TokenController 
{
    /**
     * @return void
     */
    public function getToken() {

        $codeStore = md5(get_option('home'));

        if (isset($_SESSION[$codeStore]['melhorenvio_token'])) {
            echo json_encode([
                'token' => $_SESSION[$codeStore]['melhorenvio_token'],
                'token_sandbox' => $_SESSION[$codeStore]['melhorenvio_token_sandbox'],
                'token_environment' => $_SESSION[$codeStore]['melhorenvio_token_environment']
            ]);
            die();
        }

        if (
            isset($_SESSION[$codeStore]['melhorenvio_token']) && !is_null($_SESSION[$codeStore]['melhorenvio_token']) &&
            isset($_SESSION[$codeStore]['melhorenvio_token_sandbox']) && !is_null($_SESSION[$codeStore]['melhorenvio_token_sandbox']) &&
            isset($_SESSION[$codeStore]['melhorenvio_token_environment']) && !is_null($_SESSION[$codeStore]['melhorenvio_token_environment'])
        ) {
            
            echo json_encode([
                'token' => $_SESSION[$codeStore]['melhorenvio_token'],
                'token_sandbox' => $_SESSION[$codeStore]['melhorenvio_token_sandbox'],
                'environment' => $_SESSION[$codeStore]['melhorenvio_token_environment']
            ]);
            die();
        }

        $_SESSION[$codeStore]['melhorenvio_token'] = (new token())->getToken();

        echo json_encode([
            'token' => $_SESSION[$codeStore]['melhorenvio_token']['token'],
            'token_sandbox' => $_SESSION[$codeStore]['melhorenvio_token']['token_sandbox'],
            'environment' => $_SESSION[$codeStore]['melhorenvio_token']['token_environment']
        ]);
        die();
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
     * @return void
     */
    public function saveToken() 
    {
        $codeStore = md5(get_option('home'));

        unset($_SESSION[$codeStore]);
        
        if (!isset($_POST['token'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Informar o Token'
            ]);
        }

        $result = (new Token())->saveToken($_POST['token'], $_POST['token_sandbox'], $_POST['environment']);

        $_SESSION[$codeStore]['melhorenvio_token']['token']         = $_POST['token'];
        $_SESSION[$codeStore]['melhorenvio_token']['token_sandbox'] = $_POST['token_sandbox'];
        $_SESSION[$codeStore]['melhorenvio_token']['environment']   = $_POST['environment'];

        echo json_encode([
            'success' => $result
        ]);
        die();
    }
}

