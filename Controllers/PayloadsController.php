<?php

namespace Controllers;

use Models\Payload;

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
}
