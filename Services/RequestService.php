<?php

namespace Services;

use Services\ManageRequestService;
use Services\ClearDataStored;
use Models\Version;
use Models\ResponseStatus;
use Services\SessionNoticeService;

class RequestService
{
    const URL = 'https://api.melhorenvio.com/v2/me';

    const SANDBOX_URL = 'https://sandbox.melhorenvio.com.br/api/v2/me';

    const TIMEOUT = 10;

    const WP_ERROR = 'WP_Error';

    protected $token;

    protected $headers;

    protected $url;

    public function __construct()
    {
        $tokenData = (new TokenService())->get();

        if (!$tokenData) {
            return wp_send_json([
                'message' => 'Usuário não autorizado, verificar token do Melhor Envio'
            ], ResponseStatus::HTTP_UNAUTHORIZED);
        }

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
            'version-plugin-me' => Version::VERSION,
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

        $responseRemote = wp_remote_post($this->url . $route, $params);

        if (!is_array($responseRemote)) {
            if (get_class($responseRemote) === self::WP_ERROR) {
                return (object) [];
            }
        }

        $response = json_decode(
            wp_remote_retrieve_body($responseRemote)
        );

        $responseCode = (!empty($responseRemote['response']['code']))
            ? $responseRemote['response']['code']
            : null;

        if ($responseCode == ResponseStatus::HTTP_UNAUTHORIZED) {
            (new SessionNoticeService())->add(
                SessionNoticeService::NOTICE_INVALID_TOKEN,
                SessionNoticeService::NOTICE_INFO
            );
            (new ClearDataStored())->clear();
        }

        if ($responseCode != ResponseStatus::HTTP_OK) {
            (new ClearDataStored())->clear();
        }

        if (empty($response)) {
            (new ClearDataStored())->clear();
            return (object) [
                'success' => false,
                'errors' => ['Ocorreu um erro ao se conectar com a API do Melhor Envio'],
            ];
        }

        if (!empty($response->message) && $response->message == 'Unauthenticated.') {
            return (object) [
                'success' => false,
                'errors' => ['Usuário não autenticado'],
            ];
        }

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
