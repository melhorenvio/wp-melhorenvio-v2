<?php

namespace Services;

class RequestService
{
    const URL = 'https://api.melhorenvio.com/v2/me';

    const SANDBOX_URL = 'https://sandbox.melhorenvio.com.br/api/v2/me';

    const TIMEOUT = 10;

    protected $token;

    protected $headers;

    protected $url;

    public function __construct()
    {
        $tokenData = (new TokenService())->get();

        if ($tokenData['token_environment'] == 'production') {
            $this->token = $tokenData['token'];
            $this->url = self::URL;
        } else {
            $this->token = $tokenData['token_sandbox'];
            $this->url = self::SANDBOX_URL;
        }

        $this->headers = array(
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . $this->token,
        );
    }

    /**
     * Function to make a request to API Melhor Envio.
     *
     * @param string $route
     * @param string $typeRequest
     * @param array $body
     * @return object $response
     */
    public function request($route, $typeRequest, $body, $useJson = true)
    {        
        if ($useJson) {
            $body = json_encode($body);
        }

        $params = array(
            'headers' => $this->headers,
            'method'  => $typeRequest,
            'body'    => $body,
            'timeout ' => self::TIMEOUT
        );

        $response = json_decode(
            wp_remote_retrieve_body(
                wp_remote_post($this->url . $route, $params)
            )
        );

        return $response;
    }

    /**
     * treatment errors to user
     *
     * @param object $data
     * @return array $errors
     */
    private function treatmentErrors($data)
    {
        $response = [];

        if (isset($data->error)) {
            $response[] = $data->error;
        }

        if (isset($data->errors) && is_array($data->errors)) {
            foreach ($data->errors as $error) {
                if (is_array($error)) {
                    foreach ($error as $err) {
                        $response[] = $err;
                    }

                    return $response;
                }

                $response[] = $error;
            }
        }

        return [
            'success' => false,
            'message' => null,
            'errors' => $response
        ];
    }
}
