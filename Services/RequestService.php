<?php

namespace Services;

use Models\Version;

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
            'version-wordpress' => Version::VERSION,
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

        $errors =  $this->treatmentErrors($response);

        if (!empty($errors)) {
            return (object) [
                'success' => false,
                'errors' => $errors,
            ];
        }

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
        $errorsResponse = [];
        $errors = [];

        if (!empty($data->error)) {
            $errors[] = $data->error;
        }

        if (!empty($data->errors)) {
            foreach ($data->errors as $errors) {
                $errorsResponse[] = $errors;
            }
        }

        if (!empty($errorsResponse) && is_array($errorsResponse)) {
            foreach ($errorsResponse as $error) {
                $errors[] = end($error);
            }
        }

        return $errors;
    }
}
