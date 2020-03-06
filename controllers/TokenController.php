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
                'token' => $_SESSION[$codeStore]['melhorenvio_token']
            ]);
            die();
        }

        if (isset($_SESSION[$codeStore]['melhorenvio_token']) && !is_null($_SESSION[$codeStore]['melhorenvio_token'])) {
            
            echo json_encode([
                'token' => $_SESSION[$codeStore]['melhorenvio_token']
            ]);
            die();
        }

        $_SESSION[$codeStore]['melhorenvio_token'] = (new token())->getToken();

        echo json_encode([
            'token' => $_SESSION[$codeStore]['melhorenvio_token']
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

        $result = (new Token())->saveToken($_POST['token']);

        $_SESSION[$codeStore]['melhorenvio_token'] = $_POST['token'];

        echo json_encode([
            'success' => $result
        ]);
        die();
    }
}

