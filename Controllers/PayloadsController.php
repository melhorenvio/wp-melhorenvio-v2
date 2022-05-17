<?php

namespace Controllers;

use Models\Payload;
use Services\PayloadService;

class PayloadsController
{
    const PAYLOAD_NOT_FOUND = 'Payload não encontrado para o post id ';

    const PAYLOAD_ERROR = 'Ocorreu um erro ao obter o payload';

    /**
     * controller to show payload by order
     *
     * @param int $post_id
     * @return json
     */
    public function show($post_id)
    {
        try {
            $payload = (new PayloadService())->getPayloadHideImportantData($post_id);

            if (empty($payload)) {
                return wp_send_json([
                    'message' => self::PAYLOAD_NOT_FOUND . $post_id
                ], 404);
            }

            return wp_send_json($payload, 200);
        } catch (\Exception $exception) {
            return wp_send_json([
                'message' => self::PAYLOAD_ERROR
            ], 400);
        }
    }

    /**
     * controller to show payload by order
     *
     * @param int $post_id
     * @return json
     */
    public function showLogged($post_id)
    {
        try {
            $payload = (new Payload())->get($post_id);

            if (empty($payload)) {
                return wp_send_json([
                    'message' => self::PAYLOAD_NOT_FOUND . $post_id
                ], 404);
            }

            return wp_send_json($payload, 200);
        } catch (\Exception $exception) {
            return wp_send_json([
                'message' => self::PAYLOAD_ERROR
            ], 400);
        }
    }

    /**
     * function to destroy payload by post id.
     *
     * @param int $post_id
     * @return json
     */
    public function destroy($post_id)
    {
        try {
            if ((new Payload())->destroy($post_id)) {
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
     * @param int $post_id
     * @return json
     */
    public function showPayloadCart($post_id, $service)
    {
        try {
            $payload = (new PayloadService())->getPayloadToCart($post_id, $service);

            if (empty($payload)) {
                return wp_send_json([
                    'message' => self::PAYLOAD_NOT_FOUND . $post_id
                ], 404);
            }

            return wp_send_json($payload, 200);
        } catch (\Exception $exception) {
            return wp_send_json([
                'message' => self::PAYLOAD_ERROR
            ], 400);
        }
    }
}
