<?php

namespace MelhorEnvio\Services;

use MelhorEnvio\Helpers\EscapeAllowedTags;

class TrackingService {



	const TRACKING_MELHOR_ENVIO = 'melhorenvio_tracking';

	/**
	 * Save tracking order
	 *
	 * @param int    $orderId
	 * @param string $tracking
	 * @return void
	 */
	public function addTrackingOrder( $orderId, $tracking ) {
		add_post_meta( $orderId, self::TRACKING_MELHOR_ENVIO, $tracking, true );
	}

	/**
	 * Function to get tracking order
	 *
	 * @param int $orderId
	 * @return string $tracking
	 */
	public function getTrackingOrder( $orderId ) {
		$data = get_post_meta( $orderId, self::TRACKING_MELHOR_ENVIO, true );

		if ( ! empty( $data ) ) {
			return $data;
		}

		$data = ( new OrderQuotationService() )->getData( $orderId );

		if ( empty( $data ) || empty( $data['order_id'] ) ) {
			return null;
		}

		if ( ! empty( $data['tracking'] ) ) {
			return $data['tracking'];
		}

		$data = ( new OrderService() )->getInfoOrder( $data['order_id'] );

		if ( empty( $data ) ) {
			return null;
		}

		if ( is_array( $data ) ) {
			$data = end( $data );
		}

		$tracking = ( ! empty( $data->tracking ) ) ? $data->tracking : null;

		if ( empty( $tracking ) ) {
			$tracking = ( ! empty( $data->self_tracking ) ) ? $data->self_tracking : null;
		}

		if ( ! empty( $tracking ) ) {
			$this->addTrackingOrder( $orderId, $tracking );
			return $tracking;
		}

		return null;
	}

	/**
	 * Adds a new column to the "My Orders" table in the account.
	 */
	public function createTrackingColumnOrdersClient() {
		add_filter(
			'woocommerce_my_account_my_orders_columns',
			function ( $columns ) {
				$newColumns = array();
				foreach ( $columns as $key => $name ) {
					$newColumns[ $key ] = $name;
					if ( 'order-status' === $key ) {
						$newColumns['tracking'] = __( 'Rastreio', 'textdomain' );
					}
				}
				return $newColumns;
			}
		);

		$this->addTrackingToOrderClients();
	}

	/**
	 * Adds data to the custom "tracking to" column in "My Account > Orders".
	 */
	private function addTrackingToOrderClients() {
		add_action(
			'woocommerce_my_account_my_orders_column_tracking',
			function ( $order ) {

				$text = 'Não disponível';
				if ( $this->isWaitingToBePosted( $order ) ) {
					$text = 'Aguardando postagem';
					$data = ( new TrackingService() )->getTrackingOrder( $order->get_id() );
				}

				echo wp_kses(
					( ! empty( $data ) )
						? sprintf( "<a target='_blank' href='https://melhorrastreio.com.br/rastreio/%s'>%s</a>", $data, $data )
						: $text,
					EscapeAllowedTags::allow_tags( array( 'div', 'a' ) )
				);
			}
		);
	}

	/**
	 * @param $order
	 * @return bool
	 */
	private function isWaitingToBePosted( $order ) {
		return in_array( $order->get_status(), array( 'processing', 'completed' ) );
	}
}
