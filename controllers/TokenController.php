<?php

namespace Controllers;

use Models\Token;

class TokenController {

    public function getToken() {
        $token = new token();
        echo json_encode([
            'token' => $token->getToken()
        ]);
        die();
    }

    public function saveToken() {
        $token = new Token();
        $result = $token->saveToken($_POST['token']);
        echo json_encode([
            'success' => $result
        ]);
        die();
    }
}

