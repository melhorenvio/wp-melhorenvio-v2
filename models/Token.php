<?php

namespace Models;

class Token {

    public function __construct() {

    }

    public function getToken() {
        $token = get_option('melhorenvio_token');
        if (!$token) {
            return '';
        }
        return $token;
    }

    public function saveToken($token) {
        return update_option('melhorenvio_token', $token, true);
    }
}
