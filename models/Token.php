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

        try {
            if (!WP_Session_Tokens::verify($_SESSION[$codeStore]['melhorenvio_token'])) {
                add_action( 'admin_notices', function(){
                    echo sprintf('<div class="error">
                        <p>%s</p>
                    </div>', 'O token do Melhor Envio expirou.');
                });
            }
        } catch(Exception $e) {
            echo 'Exceção capturada: ',  $e->getMessage(), "\n";
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
