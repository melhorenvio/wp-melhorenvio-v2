<?php

namespace Services;

use Models\ShippingService;

/**
 * Class GetMethodIdSelectedService
 * @package Services
 */
class GetMethodIdSelectedService
{
    const DEFAULT_METHOD_ID = 'melhorenvio_correios_sedex';

    /**
     * Function to get method_id selected by postId
     *
     * @param $postId
     * @return int
     */
    public function get($postId)
    {
        $order = wc_get_order( $postId );

        $shipping_item_data = end($order->get_items( 'shipping' ))->get_data();

        $method_id = (empty($shipping_item_data['method_id'])) 
            ? self::DEFAULT_METHOD_ID  
            : $shipping_item_data['method_id'];

        return ShippingService::getCodeByMethodId($method_id);
    }
}
