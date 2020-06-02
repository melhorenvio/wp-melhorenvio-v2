<?php

namespace Services;

class TokenService
{
    const OPTION_TOKEN = 'wpmelhorenvio_token';

    const OPTION_TOKEN_SANDBOX = 'wpmelhorenvio_token_sandbox';

    const OPTION_TOKEN_ENVIRONMENT = 'wpmelhorenvio_token_environment';

    /**
     * Get token Melhor Envio.
     *
     * @return string $token
     */
    public function get()
    {
        $token = get_option(self::OPTION_TOKEN); 
        $token_sandbox = get_option(self::OPTION_TOKEN_SANDBOX); 
        $token_environment = get_option(self::OPTION_TOKEN_ENVIRONMENT); 
		
		if (is_null($token_environment) || empty($token_environment)) {
			$token_environment = 'production';
		}
			
        return [
            'token' => $token,
            'token_sandbox' => $token_sandbox,
            'token_environment' => $token_environment,
        ];
    }

    /**
     * Service to save token Melhor Envio.
     *
     * @param string $token
     * @param string $token_sandbox
     * @param string $token_environment
     * @return array $response
     */
    public function save($token, $token_sandbox, $token_environment)
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
        update_option(self::OPTION_TOKEN_SANDBOX, $token_sandbox, true);
        update_option(self::OPTION_TOKEN_ENVIRONMENT, $token_environment, true);

        return [
            'success' => true,
            'message' => 'Token salvo com sucesso'
        ];
    }

    public function check()
    {
        $dataToken = $this->get();

        return [
            'environment' => $dataToken['token_environment'],
            'production' => substr($dataToken['token'], 0, 30) . '...',
            'sandbox' => substr($dataToken['token_sandbox'], 0, 30) . '...'
        ];

    }
}
