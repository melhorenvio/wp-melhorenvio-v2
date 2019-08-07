<?php

namespace Models;

use Controllers\tokenController;

class User 
{
    const URL = 'https://api.melhorenvio.com';

    const OPTION_USER_INFO = 'melhorenvio_user_info';

    const SESSION_USER_INFO = 'melhorenvio_user_info';

    /**
     * Return an array contain info about user
     *
     * @return Array
     */
    public function get() 
    {
        // Get info on session
        $codeStore = md5(get_option('home'));
        /*
        if (isset($_SESSION[$codeStore][self::SESSION_USER_INFO])) {
            return array(
                'success' => true,
                'origin'  => 'session',
                'data'    => $_SESSION[$codeStore][self::SESSION_USER_INFO]
            );
        }

        // Get info on database wordpress
        $optionData = get_option(self::OPTION_USER_INFO, true);

        if (!is_bool($optionData)) {

            $_SESSION[$codeStore][self::SESSION_USER_INFO] = $optionData;

            return array(
                'success' => true,
                'origin'  => 'database',
                'data'    => $optionData
            );
        }
        */
        // Get info on API Melhor Envio
        $token = (new TokenController())->token();
        
        $params = array(
            'headers'=> array(
                'Content-Type' => 'application/json',
                'Accept'=>'application/json',
                'Authorization' => 'Bearer '.$token
            )
        );

        $response = wp_remote_retrieve_body(
            wp_remote_get(self::URL . '/v2/me', $params)
        );

        if (is_null($response)) {
            return array(
                'success' => false,
                'message' => 'Erro ao consultar o servidor'
            );  
        }

        $data = get_object_vars(json_decode($response));

        $_SESSION[$codeStore][self::SESSION_USER_INFO] = $data;

        add_option(self::OPTION_USER_INFO, $data);

        return array(
            'success' => true,
            'origin'  => 'api',
            'data'    => $data,
        );
    }

    /**
     * @return Array
     */
    public function getBalance() 
    {
        $token = (new TokenController())->token();

        $params = array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept'=>'application/json',
                'Authorization' => 'Bearer '.$token
            )
        );

        $response = json_decode(
            wp_remote_retrieve_body(
                wp_remote_get(self::URL . '/v2/me/balance', $params)
            )
        );

        if (isset($response->balance)) {
            return array(
                'success' => true,
                'balance' => 'R$' . number_format($response->balance, 2, ',', '.'),
                'value' => $response->balance
            );
        }
        
        return array(
            'success' => false,
            'message' => 'Erro ao conectar a API'
        );
    }

    /**
     * Reset data about user on Database and Session
     *
     * @return void
     */
    public function resetData()
    {
        $codeStore = md5(get_option('home'));

        delete_option(self::OPTION_USER_INFO, true);   

        unset($_SESSION[$codeStore][self::SESSION_USER_INFO]);
    }
}
