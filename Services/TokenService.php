<?php

namespace Services;

class TokenService
{
    const OPTION_TOKEN = 'wpmelhorenvio_token';

    /**
     * Get token Melhor Envio.
     *
     * @return string $token
     */
    public function get()
    {
        $token = get_option(self::OPTION_TOKEN); 

        if (!$token) {
            return [
                'success' => false,
                'message' => 'Token nÃ£o salvo'
            ];
        }

        return $token;
    }

    /**
     * Service to save token Melhor Envio.
     *
     * @param string $token
     * @return array $response
     */
    public function save($token)
    {
        $tokenSaved = $this->get();

        if (isset($tokenSaved->success) && !$tokenSaved->success) {
            if (add_option(self::OPTION_TOKEN, $token)) {

                return [
                    'success' => true,
                    'message' => 'Token salvo com sucesso'
                ];
            }
        }

        update_option(self::OPTION_TOKEN, $token, true);

        return [
            'success' => true,
            'message' => 'Token salvo com sucesso'
        ];
    }
}
