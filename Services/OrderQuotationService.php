<?php

namespace Services;

class OrderQuotationService
{
    const POST_META_ORDER_QUOTATION = 'melhorenvio_cotation_v2';

    const POST_META_ORDER_DATA = 'melhorenvio_status_v2';

    /**
     * Function to get a quotation by order in postmetas by wordpress.
     *
     * @param integer $order_id
     * @return object $quotation
     */
    public function getQuotation($order_id)
    {
        $quotation = get_post_meta(self::POST_META_ORDER_QUOTATION, $order_id);

        if (!$quotation) {
            return (object) [
                'error' => true,
                'message' => 'Quotation not found in database'
            ];
        }

        return $quotation;
    }

    /**
     * Get postmeta data by order (Status, order_id, protocol).
     *
     * @param int $order_id
     * @return array $data
     */
    public function getStatus($order_id)
    {
        return get_post_meta($order_id, self::POST_META_ORDER_DATA, true);
    }

    /**
     * Function to update data quotation by order.
     * 
     * @param int $order_id
     * @param string $order_melhor_envio_id
     * @param string $protocol
     * @param string $status
     * @param int $choose_method
     * @return void
     */
    public function updateDataCotation($order_id, $order_melhor_envio_id, $protocol, $status, $choose_method) 
    {
        $data = [
            'choose_method' => $choose_method,
            'order_id' => $order_melhor_envio_id,
            'protocol' => $protocol,
            'status' => $status,
            'created' => date('Y-m-d H:i:s')
        ];

        delete_post_meta($order_id, self::POST_META_ORDER_DATA);
        add_post_meta($order_id, self::POST_META_ORDER_DATA, $data);
    }
}