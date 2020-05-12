<?php

namespace Models;

class Token 
{
    /**
     * @return void
     */
    public function getToken() 
    {
        $token = get_option('wpmelhorenvio_token');
        $token_sandbox = get_option('wpmelhorenvio_token_sandbox');
        $token_environment = get_option('wpmelhorenvio_token_environment');

        return [
            'token' => $token,
            'token_sandbox' => $token_sandbox,
            'token_environment' => $token_environment
        ];
        
    }

    /**
     * @param string $token
     * @param string $token_sandbox
     * @param string $token_environment
     * @return array $data
     */
    public function saveToken($token, $token_sandbox, $environment) 
    {   
        delete_option('wpmelhorenvio_token');
        delete_option('wpmelhorenvio_token_sandbox');
        delete_option('wpmelhorenvio_token_environment');

        return [
            'token' => add_option('wpmelhorenvio_token', $token, true),
            'token_sandbox' => add_option('wpmelhorenvio_token_sandbox', $token_sandbox, true),
            'token_environment' => add_option('wpmelhorenvio_token_environment', $environment, true)
        ];
    }

    
}
