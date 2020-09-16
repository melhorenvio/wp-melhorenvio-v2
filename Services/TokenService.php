<?php

namespace Services;

use Models\Token;

class TokenService
{
    /**
     * Get token Melhor Envio.
     *
     * @return string $token
     */
    public function get()
    {
        return (new Token())->get();
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
        $result = (new Token())->save($token, $tokenSandbox, $tokenEnvironment);

        if (!empty($result['token']) && !empty($result['token_environment'])) {
            return [
                'success' => true,
                'message' => 'Token salvo com sucesso'
            ];
        }

        return [
            'success' => false,
            'message' => 'Ocorreu um erro ao salvar o token'
        ];
    }

    /**
     * function used in test to verify if has tokens.
     *
     * @return array
     */
    public function check()
    {
        $dataToken = $this->get();

        return [
            'environment' => $dataToken['token_environment'],
            'production' => substr($dataToken['token'], 0, 30) . '...',
            'sandbox' => substr($dataToken['token_sandbox'], 0, 30) . '...'
        ];
    }

    public function isValide($token)
    {
    }
}
