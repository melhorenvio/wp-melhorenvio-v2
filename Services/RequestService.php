<?php

namespace Services;

class RequestService
{
    const URL = 'https://api.melhorenvio.com';

    const TIMEOUT = 10;

    protected $token;

    protected $headers;
    
    public function __construct()
    {
        $this->token = (new TokenService())->get();

        $this->headers = array(
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer '.$this->token,
        );
    }

    /**
     * Function to make a request to API Melhor Envio.
     *
     * @param string $route
     * @param string $type_request
     * @param array $body
     * @return array $response
     */
    public function request($route, $type_request, $body)
    {
        try {

            $params = array(
                'headers' =>  $this->headers,
                'method' => $type_request,
                'body'   => json_encode($body),
                'timeout'=> self::TIMEOUT
            );

            $response = json_decode(
                wp_remote_retrieve_body(
                    wp_remote_post(self::URL . '/v2/me' . $route, $params)
                )
            );

            if (isset($response->errors)) {
                return $this->treatmentErrors($response->errors);
            }

            return $response;

        } catch (\Exception $excption) {

        }
    }

    /**
     * treatment errors to user
     *
     * @param array $data
     * @return array $errors
     */
    private function treatmentErrors($data)
    {
        $response = [];

        foreach ($data as $error) {
            $response[] = end($error);
        }

        return [
            'success' => false,
            'message' => $response  
        ];
    }
}
