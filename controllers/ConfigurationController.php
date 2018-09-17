<?php

namespace MelhorEnvio;

class ConfigurationController {

    public function __construct(){

    }

    public function saveToken($tokenUser) {

        $token = get_option('melhorenvio_token');

        if (!$token or empty($token)) {
            add_option('melhorenvio_token', $tokenUser);
        }

        update_option('melhorenvio_token', $tokenUser,true);
        return get_option('melhorenvio_token');
    }
}

