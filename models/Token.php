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
        if (!$token) {
            return '';
        }
        
        return $token;
    }

    /**
     * @param [type] $token
     * @return void
     */
    public function saveToken($token) 
    {
        return update_option('wpmelhorenvio_token', $token, true);
    }

    
}
