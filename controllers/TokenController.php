<?php

namespace Controllers;

use Models\Token;

class TokenController 
{
    /**
     * @return void
     */
    public function getToken() {
        echo json_encode([
            'token' => (new token())->getToken()
        ]);
        die();
    }

    /**
     * @return void
     */
    public function saveToken() 
    {
        if (!isset($_POST['token'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Informar o Token'
            ]);
        }

        $result = (new Token())->saveToken($_POST['token']);
        echo json_encode([
            'success' => $result
        ]);
        die();
    }
}

