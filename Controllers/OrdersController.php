<?php

namespace MelhorEnvio\Controllers;

use MelhorEnvio\Helpers\SanitizeHelper;
use MelhorEnvio\Helpers\WpNonceValidatorHelper;
use MelhorEnvio\Services\OrdersProductsService;
use MelhorEnvio\Services\BuyerService;
use MelhorEnvio\Services\CartService;
use MelhorEnvio\Services\OrderService;
use MelhorEnvio\Services\OrderQuotationService;
use MelhorEnvio\Services\ListOrderService;
use MelhorEnvio\Services\OrderInvoicesService;

class OrdersController {

	const NOT_FOUND_ORDER_ID = 'Informar o ID do pedido';

	const WP_NONCE = '_wpnonce';

	/**
	 * Function to search for orders in the order panel
	 *
	 * @return json
	 */
	public function getOrders() {

		WpNonceValidatorHelper::check( $_GET[ self::WP_NONCE ], 'orders' );

		unset( $_GET['action'] );
		$orders = ( new ListOrderService() )->getList( SanitizeHelper::apply( $_GET ) );
		return wp_send_json( $orders, 200 );
	}

	/**
	 * Function to search for an order quote.
	 *
	 * @param int $id
	 * @return array
	 */
	public function getOrderQuotationByOrderId( $id ) {
		$data = ( new OrderQuotationService() )->getQuotation( $id );
		return wp_send_json( $data, 200 );
	}

	/**
	 * Function to add the order to the shopping cart
	 *
	 * @param int  $post_id
	 * @param int  $service
	 * @param bool $nonCommercial
	 * @return json
	 */
	public function addCart() {

		WpNonceValidatorHelper::check( $_GET[ self::WP_NONCE ], 'orders' );

		$postId = SanitizeHelper::apply( $_GET['post_id'] );

		$service = SanitizeHelper::apply( $_GET['service'] );

		$products = ( new OrdersProductsService() )->getProductsOrder( $postId );

		$buyer = ( new BuyerService() )->getDataBuyerByOrderId( $postId );

		$result = ( new CartService() )->add(
			$postId,
			$products,
			$buyer,
			$service
		);

		if ( empty( $result['success'] ) && isset( $result['errors'] ) && $result['errors'] == 'validation.nfe' ) {
			$result['errors'] = 'A chave e a nota fiscal estão incorretas, por favor verificar as mesmas';
		}

		if ( ! empty( $result['errors'] ) ) {
			return wp_send_json(
				array(
					'success' => false,
					'errors'  => array( $result['errors'] ),
				),
				400
			);
		}

		return wp_send_json( $result, 200 );
	}

	/**
	 * Function to add order in cart Melhor Envio.
	 *
	 * @param int $post_id
	 * @param int $service_id
	 * @return json $results
	 */
	public function sendOrder() {

		WpNonceValidatorHelper::check( $_GET[ self::WP_NONCE ], 'orders' );

		if ( empty( $_GET['post_id'] ) ) {
			return wp_send_json(
				array(
					'success' => false,
					'errors'  => array( self::NOT_FOUND_ORDER_ID ),
				),
				412
			);
		}

		if ( empty( $_GET['service_id'] ) ) {
			return wp_send_json(
				array(
					'success' => false,
					'errors'  => array( 'Informar o ID do serviço selecionado' ),
				),
				412
			);
		}

		$postId = SanitizeHelper::apply( $_GET['post_id'] );

		$orderId = null;

		$serviceId = SanitizeHelper::apply( $_GET['service_id'] );

		$status = null;

		$orderQuotationService = new OrderQuotationService();

		$dataOrder = $orderQuotationService->getData( $postId );

		if ( ! empty( $dataOrder['order_id'] ) ) {
			$orderId = $dataOrder['order_id'];
		}

		if ( ! empty( $dataOrder['status'] ) ) {
			$status = $dataOrder['status'];
		}

		if ( empty( $status ) && empty( $orderId ) ) {
			$products = ( new OrdersProductsService() )->getProductsOrder( $postId );

			$buyer = ( new BuyerService() )->getDataBuyerByOrderId( $postId );

			$cartResult = ( new CartService() )->add(
				$postId,
				$products,
				$buyer,
				$serviceId
			);

			if ( empty( $cartResult['order_id'] ) ) {
				$orderQuotationService->removeDataQuotation( $postId );

				if ( isset( $cartResult['errors'] ) ) {
					return wp_send_json(
						array(
							'success' => false,
							'errors'  => $cartResult['errors'],
						),
						400
					);
				}

				return wp_send_json(
					array(
						'success' => false,
						'errors'  => (array) 'Ocorreu um erro ao envio o pedido para o carrinho de compras do Melhor Envio.',
					),
					400
				);
			}

			$orderId = $cartResult['order_id'];
		}

		$paymentResult = ( new OrderService() )->payByOrderId( $postId, $orderId );

		if ( empty( $paymentResult['order_id'] ) ) {
			( new OrderQuotationService() )->removeDataQuotation( $postId );

			( new CartService() )->remove( $postId, $cartResult['order_id'] );

			if ( isset( $paymentResult['errors'] ) ) {
				return wp_send_json(
					array(
						'success' => false,
						'errors'  => $paymentResult['errors'],
					),
					400
				);
			}

			return wp_send_json(
				array(
					'success' => false,
					'message' => (array) 'Ocorreu um erro ao pagar o pedido no Melhor Envio.',
					'result'  => $paymentResult,
				),
				400
			);
		}

		$labelResult = ( new OrderService() )->createLabel( $postId );

		return wp_send_json(
			array(
				'success' => true,
				'message' => (array) 'Pedido gerado com sucesso',
				'data'    => $labelResult,
			),
			200
		);
	}

	/**
	 * Function to remove order on cart Melhor Envio.
	 *
	 * @param int $order_id
	 * @return json $response
	 */
	public function removeOrder() {

		WpNonceValidatorHelper::check( $_GET[ self::WP_NONCE ], 'orders' );

		if ( ! isset( $_GET['order_id'] ) ) {
			return wp_send_json(
				array(
					'success' => false,
					'message' => self::NOT_FOUND_ORDER_ID,
				),
				400
			);
		}

		if ( ! ( new CartService() )->remove( SanitizeHelper::apply( $_GET['id'] ), SanitizeHelper::apply( $_GET['order_id'] ) ) ) {
			return wp_send_json(
				array(
					'success' => false,
					'message' => 'Ocorreu um erro ao remove o pedido do carrinho',
				),
				400
			);
		}

		return wp_send_json(
			array(
				'success' => true,
				'message' => 'Pedido removido do carrinho de compras',
			),
			200
		);
	}

	/**
	 * Function to cancel orderm on api Melhor Envio.
	 *
	 * @param int $post_id
	 * @return array $response
	 */
	public function cancelOrder() {

		WpNonceValidatorHelper::check( $_GET[ self::WP_NONCE ], 'orders' );

		if ( ! isset( $_GET['post_id'] ) ) {
			return wp_send_json(
				array(
					'success' => false,
					'message' => array( self::NOT_FOUND_ORDER_ID ),
				),
				400
			);
		}

		$postId = SanitizeHelper::apply( $_GET['post_id'] );

		$result = ( new OrderService() )->cancel( $postId );

		if ( empty( end( $result )->canceled ) ) {
			return wp_send_json(
				array(
					'success' => false,
					'message' => array( 'Ocorreu um erro ao cancelar o pedido' ),
				),
				400
			);
		}

		return wp_send_json(
			array(
				'success' => true,
				'message' => array(
					sprintf(
						'Pedido %s cancelado com sucesso',
						$postId
					),
				),
			),
			200
		);
	}

	/**
	 * Function to pay a order Melhor Envio.
	 *
	 * @param int $order_id
	 * @return array $response
	 */
	public function payTicket() {
		$posts = explode( ',', SanitizeHelper::apply( $_GET['id'] ) );

		$result = ( new OrderService() )->pay( $posts );

		if ( ! isset( $result['purchase_id'] ) ) {
			return wp_send_json(
				array(
					'success' => false,
					'message' => 'Ocorreu um erro ao realizar o pagamento',
				),
				400
			);
		}

		return wp_send_json(
			array(
				'success' => true,
				'message' => 'Pedido pago',
				'data'    => $result,
			),
			200
		);
	}

	/**
	 * Function to create a label on Melhor Envio.
	 *
	 * @param int $post_id
	 * @return array $response
	 */
	public function createTicket() {
		$result = ( new OrderService() )->createLabel( SanitizeHelper::apply( $_GET['id'] ) );

		if ( empty( $result['order_id'] ) ) {
			return wp_send_json(
				array(
					'success' => false,
					'message' => 'Ocorreu um erro ao gerar a etiqueta',
				),
				400
			);
		}

		return wp_send_json(
			array(
				'success' => true,
				'message' => 'Pedido gerado',
				'data'    => $result,
			),
			200
		);
	}

	/**
	 * Function to print a label on Melhor Envio.
	 *
	 * @param int $post_id
	 * @return array $response
	 */
	public function printTicket() {

		WpNonceValidatorHelper::check( $_GET[ self::WP_NONCE ], 'orders' );

		$result = ( new OrderService() )->printLabel( SanitizeHelper::apply( $_GET['id'] ) );

		if ( empty( $result->url ) ) {
			return wp_send_json(
				array(
					'success' => false,
					'message' => 'Ocorreu um erro ao imprimir a etiqueta',
				),
				400
			);
		}

		return wp_send_json(
			array(
				'success' => true,
				'message' => 'Pedido impresso',
				'data'    => $result,
			),
			200
		);
	}

	/**
	 * Function to make a step by step to printed any labels
	 *
	 * @param array $ids
	 * @return array $response;
	 */
	public function buyOnClick() {
		if ( ! isset( $_GET['ids'] ) ) {
			return wp_send_json(
				array(
					'success' => false,
					'message' => 'Informar o IDs dos pedidos',
				),
				400
			);
		}

		$result = ( new OrderService() )->buyOnClick( SanitizeHelper::apply( $_GET['ids'] ) );

		if ( isset( $result['url'] ) ) {
			return wp_send_json(
				array(
					'success' => true,
					'errors'  => $result['errors'],
					'url'     => $result['url'],
				),
				200
			);
		}

		return wp_send_json(
			array(
				'success' => false,
				'errors'  => $result['errors'],
			),
			400
		);
	}

	/**
	 * Funton to insert invoice in order
	 *
	 * @param int $number
	 * @param int $key
	 *
	 * @return json
	 */
	public function insertInvoiceOrder() {

		WpNonceValidatorHelper::check( $_GET[ self::WP_NONCE ], 'orders' );

		unset( $_GET['action'] );

		if ( ! isset( $_GET['id'] ) || ! isset( $_GET['number'] ) || ! isset( $_GET['key'] ) ) {
			return wp_send_json(
				array(
					'success' => false,
					'message' => 'Campos ID, number, key são obrigatorios',
				),
				400
			);
		}

		$id = SanitizeHelper::apply( $_GET['id'] );

		$result = ( new OrderInvoicesService() )->insertInvoiceOrder(
			$id,
			SanitizeHelper::apply( $_GET['key'] ),
			SanitizeHelper::apply( $_GET['number'] )
		);

		if ( ! $result ) {
			return wp_send_json(
				array(
					'message' => 'Ocorreu um erro ao atualizar os documentos',
				),
				400
			);
		}

		return wp_send_json(
			array(
				'message' => (array) sprintf( 'Documentos do pedido %d atualizados', $id ),
			),
			200
		);
	}
}
