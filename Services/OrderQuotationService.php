<?php

namespace Service;

class OrderQuotationService
{
    const POST_META_ORDER_COTATION = 'melhorenvio_cotation_v2';

    /**
     * Function to get a quotation by order in postmetas by wordpress.
     *
     * @param integer $order_id
     * @return object $quotation
     */
    public function getQuotation($order_id)
    {
        $quotation = get_post_meta(self::POST_META_ORDER_COTATION, $order_id);

        if (!$quotation) {
            return (object) [
                'error' => true,
                'message' => 'Quotation not found in database'
            ];
        }

        return $quotation;
    }

    public function saveQuotation($quotation)
    {
        add_post_meta($order_id, self::POST_META_ORDER_COTATION, $quotation);
    }

    /**
     * Function to update data quotation by order.
     * 
     * @param int $order_id
     * @param object $quotation
     * @param string $status
     * @return void
     */
    public function updateDataCotation($order_id, $data, $status) 
    {
        var_dump('teste');die;
        $newData = [];

        $newData['choose_method'] = $data['choose_method'];
        $newData['protocol'] = $data['protocol'];
        $newData['order_id'] = $data['order_id'];
        $newData['status'] = $status;
        $newData['created'] = date('Y-m-d H:i:s');
        
        delete_post_meta($order_id, 'melhorenvio_status_v2');
        add_post_meta($order_id, 'melhorenvio_status_v2', $newData);
    }
}