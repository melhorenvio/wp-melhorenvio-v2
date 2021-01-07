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
        $method_id = self::DEFAULT_METHOD_ID;
        foreach( $order->get_items( 'shipping' ) as $item_id => $shipping_item_obj ){
            $shipping_item_data = $shipping_item_obj->get_data();
            $method_id = $shipping_item_data['method_id'];
        }

        return ShippingService::getCodeByMethodId($method_id);
    }
}
