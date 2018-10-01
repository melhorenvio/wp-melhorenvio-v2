<?php

namespace Models;

class Token {

    public function __construct() {

    }

    public function getToken() {
        $token = get_option('wpmelhorenvio_token');
        if (!$token) {
            return '';
        }
        return $token;
    }

    public function saveToken($token) {
        return update_option('wpmelhorenvio_token', $token, true);
    }
}
