<?php

declare(strict_types=1);

namespace MelhorEnvio\Infrastructure\WordPress\Ajax;

use MelhorEnvio\Infrastructure\WordPress\Http\MelhorEnvioApiClient;

final class QuotationAjaxHandler {

	public function __construct(
		private readonly MelhorEnvioApiClient $apiClient,
	) {}

	public function register(): void {
		add_action( 'wp_ajax_me_quote', array( $this, 'handle' ) );
		add_action( 'wp_ajax_nopriv_me_quote', array( $this, 'handle' ) );
	}

	private const LOG_CONTEXT = array( 'source' => 'melhor-envio-cotacao' );

	public function handle(): void {
		check_ajax_referer( 'me_quote', 'nonce' );

		$cep       = sanitize_text_field( wp_unslash( $_POST['cep'] ?? '' ) );
		$productId = (int) ( $_POST['product_id'] ?? 0 );
		$quantity  = max( 1, (int) ( $_POST['quantity'] ?? 1 ) );

		$cep = preg_replace( '/\D/', '', $cep );

		if ( strlen( $cep ) !== 8 ) {
			wc_get_logger()->warning( sprintf( 'AJAX cotação: CEP inválido recebido: "%s"', $cep ), self::LOG_CONTEXT );
			wp_send_json_error( array( 'message' => __( 'CEP inválido.', 'melhor-envio-cotacao' ) ) );
		}

		if ( ! $productId ) {
			wc_get_logger()->warning( 'AJAX cotação: product_id ausente.', self::LOG_CONTEXT );
			wp_send_json_error( array( 'message' => __( 'Produto não encontrado.', 'melhor-envio-cotacao' ) ) );
		}

		$product = wc_get_product( $productId );

		if ( ! $product ) {
			wc_get_logger()->warning( sprintf( 'AJAX cotação: produto %d não encontrado.', $productId ), self::LOG_CONTEXT );
			wp_send_json_error( array( 'message' => __( 'Produto não encontrado.', 'melhor-envio-cotacao' ) ) );
		}

		$fromCep = preg_replace( '/\D/', '', get_option( 'woocommerce_store_postcode', '' ) );

		if ( empty( $fromCep ) ) {
			wc_get_logger()->warning( 'AJAX cotação: CEP de origem não configurado.', self::LOG_CONTEXT );
			wp_send_json_error( array( 'message' => __( 'CEP de origem não configurado.', 'melhor-envio-cotacao' ) ) );
		}

		$items = array(
			array(
				'id'              => $product->get_id(),
				'width'           => (float) ( $product->get_width() ?: 11 ),
				'height'          => (float) ( $product->get_height() ?: 2 ),
				'length'          => (float) ( $product->get_length() ?: 16 ),
				'weight'          => (float) ( $product->get_weight() ?: 0.3 ),
				'insurance_value' => (float) $product->get_price(),
				'quantity'        => $quantity,
			),
		);

		$quotations = $this->apiClient->getQuotations( $fromCep, $cep, $items );

		$result = array_map(
			static fn( $q ) => array(
				'name'          => $q['name'] ?? '',
				'company'       => $q['company']['name'] ?? '',
				'price'         => (float) ( $q['price'] ?? 0 ),
				'delivery_time' => (int) ( $q['delivery_time'] ?? 0 ),
			),
			array_values( $quotations )
		);

		wp_send_json_success( $result );
	}
}
