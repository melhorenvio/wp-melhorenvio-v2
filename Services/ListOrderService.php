<?php

namespace MelhorEnvio\Services;

use MelhorEnvio\Models\ShippingService;
use MelhorEnvio\Helpers\TranslateStatusHelper;
use MelhorEnvio\Helpers\ProductVirtualHelper;

class ListOrderService {

	/**
	 * Function to return the list of orders
	 *
	 * @param array $args
	 * @return array
	 */
	public function getList( $args ) {
		$limit    = $args['limit'];
		$skip     = $args['skip'];
		$status   = $args['status'];
		$wpstatus = $args['wpstatus'];

		$posts = $this->getPosts( $limit, $skip, $status, $wpstatus );

		if ( empty( $posts ) ) {
			return array(
				'orders' => array(),
				'load'   => false,
			);
		}

		$orders = $this->getData( $posts );

		return array(
			'orders' => $orders,
			'load'   => ( count( $orders ) == ( $limit ) ?: 5 ) ? true : false,
		);
	}

	/**
	 * Function to return the list of orders with the data of the Melhor Envio
	 *
	 * @param array $posts
	 * @return array
	 */
	private function getData( $posts ) {
		$orders = array();

		$statusMelhorEnvio = ( new OrderService() )->mergeStatus( $posts );

		$quotationService = new QuotationService();

		$buyerService = new BuyerService();

		$translateHelper = new TranslateStatusHelper();

		$productService = new OrdersProductsService();

		foreach ( $posts as $post ) {
			$postId = $post->ID;

			$invoice = ( new InvoiceService() )->getInvoice( $postId );

			$products = $productService->getProductsOrder( $postId );

			$products = ProductVirtualHelper::removeVirtuals( $products );

			$orders[] = array(
				'id'             => $postId,
				'tracking'       => $statusMelhorEnvio[ $postId ]['tracking'],
				'link_tracking'  => ( ! is_null( $statusMelhorEnvio[ $postId ]['tracking'] ) )
					? sprintf( 'https://www.melhorrastreio.com.br/rastreio/%s', $statusMelhorEnvio[ $postId ]['tracking'] )
					: null,
				'to'             => $buyerService->getDataBuyerByOrderId( $postId ),
				'status'         => $statusMelhorEnvio[ $postId ]['status'],
				'status_texto'   => $translateHelper->translateNameStatus(
					$statusMelhorEnvio[ $postId ]['status']
				),
				'order_id'       => $statusMelhorEnvio[ $postId ]['order_id'],
				'service_id'     => ( ! empty( $statusMelhorEnvio[ $postId ]['service_id'] ) )
					? $statusMelhorEnvio[ $postId ]['service_id']
					: ShippingService::CORREIOS_SEDEX,
				'protocol'       => $statusMelhorEnvio[ $postId ]['protocol'],
				'non_commercial' => is_null( $invoice['number'] ) || is_null( $invoice['key'] ),
				'invoice'        => $invoice,
				'products'       => $products,
				'quotation'      => $quotationService->calculateQuotationByPostId( $postId ),
				'link'           => admin_url() . sprintf( 'post.php?post=%d&action=edit', $postId ),
			);
		}

		return $orders;
	}

	/**
	 * Function to get posts
	 *
	 * @param int    $limit
	 * @param int    $skip
	 * @param string $status
	 * @param string $wpstatus
	 * @return array posts
	 */
	private function getPosts( $limit, $skip, $status, $wpstatus ) {
		$args = array(
			'numberposts' => ( $limit ) ?: 5,
			'offset'      => ( $skip ) ?: 0,
			'post_type'   => 'shop_order',
		);

		if ( isset( $wpstatus ) && $wpstatus != 'all' ) {
			$args['post_status'] = $wpstatus;
		} elseif ( isset( $wpstatus ) && $wpstatus == 'all' ) {
			$args['post_status'] = array_keys( wc_get_order_statuses() );
		} else {
			$args['post_status'] = 'publish';
		}

		if ( isset( $status ) && $status != 'all' ) {
			$args['meta_query'] = array(
				array(
					'key'     => 'melhorenvio_status_v2',
					'value'   => sprintf( ':"%s";', $status ),
					'compare' => 'LIKE',
				),
			);
		}

		return get_posts( $args );
	}
}
