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
        $tokenSandbox = get_option(self::OPTION_TOKEN_SANDBOX);
        $tokenEnvironment = get_option(self::OPTION_TOKEN_ENVIRONMENT);

        if (is_null($tokenEnvironment) || empty($tokenEnvironment) || $tokenEnvironment == "false" || $tokenEnvironment == "undefined") {
            $tokenEnvironment = 'production';
        }

        return [
            'token' => $token,
            'token_sandbox' => $tokenSandbox,
            'token_environment' => $tokenEnvironment,
        ];
    }

    /**
     * Service to save token Melhor Envio.
     *
     * @param string $token
     * @param string $tokenSandbox
     * @param string $tokenEnvironment
     * @return array $response
     */
    public function save($token, $tokenSandbox, $tokenEnvironment)
    {
        delete_option(self::OPTION_TOKEN);
        delete_option(self::OPTION_TOKEN_SANDBOX);
        delete_option(self::OPTION_TOKEN_ENVIRONMENT);

        add_option(self::OPTION_TOKEN, $token, true);
        add_option(self::OPTION_TOKEN_SANDBOX, $tokenSandbox, true);
        add_option(self::OPTION_TOKEN_ENVIRONMENT, $tokenEnvironment, true);

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
