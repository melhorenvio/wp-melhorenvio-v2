<?php

namespace Controllers;

use Models\Payload;
use Services\PayloadService;

class PayloadsController
{
    /**
     * controller to show payload by order
     *
     * @param int $postId
     * @return json
     */
    public function show($postId)
    {
        try {
            $payload = (new PayloadService())->getPayloadHideImportantData($postId);

            if (empty($payload)) {
                return wp_send_json([
                    'message' => 'Payload não encontrado para o post id ' . $postId
                ], 404);
            }

            return wp_send_json($payload, 200);
        } catch (\Exception $exception) {
            return wp_send_json([
                'message' => 'Ocorreu um erro ao obter o payload'
            ], 400);
        }
    }

    /**
     * controller to show payload by order
     *
     * @param int $postId
     * @return json
     */
    public function showLogged($postId)
    {
        try {
            $payload = (new Payload())->get($postId);

            if (empty($payload)) {
                return wp_send_json([
                    'message' => 'Payload não encontrado para o post id ' . $postId
                ], 404);
            }

            return wp_send_json($payload, 200);
        } catch (\Exception $exception) {
            return wp_send_json([
                'message' => 'Ocorreu um erro ao obter o payload'
            ], 400);
        }
    }

    /**
     * function to destroy payload by post id.
     *
     * @param int $postId
     * @return json
     */
    public function destroy($postId)
    {
        try {
            if ((new Payload())->destroy($postId)) {
                return wp_send_json([
                    'success' => true
                ], 200);
            }
            
            return wp_send_json([
                'success' => false,
                'error' => 'Não foi possível remover o payload'
            ], 400);
        } catch (\Exception $exception) {
            return wp_send_json([
                'success' => false,
                'error' => 'Ocorreu um erro ao remover o payload'
            ], 400);
        }
    }

    /**
     * function to retrieve payload to insert item cart.
     * 
     * @param int $postId
     * @return json
     */
    public function showPayloadCart($postId, $service)
    {
        try {
            $payload = (new PayloadService())->getPayloadToCart($postId, $service);

            if (empty($payload)) {
                return wp_send_json([
                    'message' => 'Payload não encontrado para o post id ' . $postId
                ], 404);
            }

            return wp_send_json($payload, 200);
        } catch (\Exception $exception) {
            return wp_send_json([
                'message' => 'Ocorreu um erro ao obter o payload'
            ], 400);
        }
    }
}
