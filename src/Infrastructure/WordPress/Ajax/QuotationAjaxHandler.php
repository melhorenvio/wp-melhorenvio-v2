<?php

declare(strict_types=1);

namespace MelhorEnvio\Infrastructure\WordPress\Ajax;

use MelhorEnvio\Infrastructure\WordPress\Http\MelhorEnvioApiClient;
use MelhorEnvio\Infrastructure\WordPress\Shipping\CartItemsBuilder;
use MelhorEnvio\Infrastructure\WordPress\Shipping\UnitConverter;

final class QuotationAjaxHandler {

	public function __construct(
		private readonly MelhorEnvioApiClient $apiClient,
		private readonly CartItemsBuilder $cartItemsBuilder,
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

		$package = array(
			'destination'   => array(
				'country'  => 'BR',
				'state'    => '',
				'postcode' => $cep,
			),
			'contents'      => array(),
			'contents_cost' => 0,
		);

		$zone      = \WC_Shipping_Zones::get_zone_matching_package( $package );
		$methods   = $zone->get_shipping_methods( true );
		$meMethods = array_filter( $methods, fn( $m ) => $m->id === 'melhor_envio' );

		if ( empty( $meMethods ) ) {
			wp_send_json_error( array( 'message' => __( 'Frete não disponível para este endereço.', 'melhor-envio-cotacao' ) ) );
		}

		$onlyInCartMessage = __( 'Cotação deste produto disponível apenas no carrinho.', 'melhor-envio-cotacao' );

		if ( CartItemsBuilder::isCompositeProduct( $product ) ) {
			$compositeIds = sanitize_text_field( wp_unslash( $_POST['wooco_ids'] ?? '' ) );
			$items        = $this->cartItemsBuilder->buildItemsForCompositeProduct( $product, $quantity, $compositeIds );

			if ( $items === null ) {
				wp_send_json_error(
					array( 'message' => __( 'Selecione os itens da composição antes de calcular o frete.', 'melhor-envio-cotacao' ) )
				);
			}
		} elseif ( CartItemsBuilder::isBundleProduct( $product ) ) {
			$bundleIds = sanitize_text_field( wp_unslash( $_POST['woosb_ids'] ?? '' ) );

			if ( empty( $bundleIds ) && CartItemsBuilder::bundleHasOptionalItems( $product ) ) {
				wp_send_json_error( array( 'message' => $onlyInCartMessage ) );
			}

			$items = $this->cartItemsBuilder->buildItemsForBundleProduct( $product, $quantity, $bundleIds ?: null );
		} else {
			$items = array(
				array(
					'id'              => $product->get_id(),
					'width'           => UnitConverter::toCm( (float) ( $product->get_width() ?: 11 ) ),
					'height'          => UnitConverter::toCm( (float) ( $product->get_height() ?: 2 ) ),
					'length'          => UnitConverter::toCm( (float) ( $product->get_length() ?: 16 ) ),
					'weight'          => UnitConverter::toKg( (float) ( $product->get_weight() ?: 0.3 ) ),
					'insurance_value' => (float) $product->get_price(),
					'quantity'        => $quantity,
				),
			);
		}

		$quotations = $this->apiClient->getQuotations( $fromCep, $cep, $items );

		$result = array_map(
			static fn( $q ) => array(
				'name'          => $q['name'] ?? '',
				'company'       => $q['company']['name'] ?? '',
				'price'         => (float) ( $q['custom_price'] ?? $q['price'] ?? 0 ),
				'delivery_time' => (int) ( $q['custom_delivery_time'] ?? $q['delivery_time'] ?? 0 ),
			),
			array_values( $quotations )
		);

		wp_send_json_success( $result );
	}
}
