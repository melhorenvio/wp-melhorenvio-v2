<?php

namespace Services;

class TrackingService
{
    const TRACKING_MELHOR_ENVIO = 'melhorenvio_tracking';

    /**
     * Save tracking order
     *
     * @param int $order_id
     * @param string $tracking
     * @return void
     */
    public function addTrackingOrder($order_id, $tracking)
    {
        add_post_meta($order_id, self::TRACKING_MELHOR_ENVIO, $tracking, true);
    }

    /**
     * Function to get tracking order
     *
     * @param int $order_id
     * @return string $tracking
     */
    public function getTrackingOrder($order_id)
    {
        return get_post_meta($order_id, self::TRACKING_MELHOR_ENVIO, true);
    }
}