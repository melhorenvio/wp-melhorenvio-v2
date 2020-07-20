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

    /**
     * Adds a new column to the "My Orders" table in the account.
     *
     * @param string[] $columns the columns in the orders table
     * @return string[] updated columns
     */
    public function createTrackingColumnOrdersClient()
    {
        add_filter( 'woocommerce_my_account_my_orders_columns', function ($columns) {
            $new_columns = array();
            foreach ( $columns as $key => $name ) {
                $new_columns[ $key ] = $name;
                if ( 'order-status' === $key ) {
                    $new_columns['tracking'] = __( 'Rastreio', 'textdomain' );
                }
            }
            return $new_columns;
        } );

        $this->addTrackingToOrderClients();
    }

     /**
     * Adds data to the custom "ship to" column in "My Account > Orders".
     *
     * @param \WC_Order $order the order object for the row
     */
    private function addTrackingToOrderClients()
    {
        add_action( 'woocommerce_my_account_my_orders_column_tracking', function($order){

            $data = (new TrackingService())->getTrackingOrder($order->id);

            if(empty($data)) {
                echo 'Aguardando postagem';
            } else {
                echo '<a target="_blank" href="https://melhorrastreio.com.br/rastreio/'. $data .'">' . $data . '</a>';
            }
        } );
    }
}