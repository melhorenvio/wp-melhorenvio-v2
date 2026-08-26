<?php

declare(strict_types=1);

namespace MelhorEnvio\Http\Controllers\Quotation;

use MelhorEnvio\Http\Controllers\Contracts\ControllerInterface;
use MelhorEnvio\Services\Quotation\MelhorEnvioApiClientService;
use MelhorEnvio\Services\Quotation\PostalCodeLocationClientService;
use MelhorEnvio\Services\Shipping\CartItemsBuilderService;
use MelhorEnvio\Support\UnitConverter;

final class QuotationController implements ControllerInterface {

	private MelhorEnvioApiClientService $apiClient;
	private CartItemsBuilderService $cartItemsBuilder;
	private PostalCodeLocationClientService $locationClient;

	public function __construct(
		MelhorEnvioApiClientService $apiClient,
		CartItemsBuilderService $cartItemsBuilder,
		PostalCodeLocationClientService $locationClient
	) {
		$this->apiClient        = $apiClient;
		$this->cartItemsBuilder = $cartItemsBuilder;
		$this->locationClient   = $locationClient;
	}

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
				'state'    => (string) $this->locationClient->getState( $cep ),
				'postcode' => $cep,
			),
			'contents'      => array(
				$product->get_id() => array(
					'data'     => $product,
					'quantity' => $quantity,
				),
			),
			'contents_cost' => (float) $product->get_price() * $quantity,
		);

		$zone         = \WC_Shipping_Zones::get_zone_matching_package( $package );
		$methods      = $zone->get_shipping_methods( true );
		$meMethods    = array_filter( $methods, fn( $m ) => $m->id === 'melhor_envio' );
		$otherMethods = array_filter( $methods, fn( $m ) => $m->id !== 'melhor_envio' );

		$onlyInCartMessage = __( 'Cotação deste produto disponível apenas no carrinho.', 'melhor-envio-cotacao' );

		$result = array();

		if ( ! empty( $meMethods ) ) {
			if ( CartItemsBuilderService::isCompositeProduct( $product ) ) {
				$compositeIds = sanitize_text_field( wp_unslash( $_POST['wooco_ids'] ?? '' ) );
				$items        = $this->cartItemsBuilder->buildItemsForCompositeProduct( $product, $quantity, $compositeIds );

				if ( $items === null ) {
					wp_send_json_error(
						array( 'message' => __( 'Selecione os itens da composição antes de calcular o frete.', 'melhor-envio-cotacao' ) )
					);
				}
			} elseif ( CartItemsBuilderService::isBundleProduct( $product ) ) {
				$bundleIds = sanitize_text_field( wp_unslash( $_POST['woosb_ids'] ?? '' ) );

				if ( empty( $bundleIds ) && CartItemsBuilderService::bundleHasOptionalItems( $product ) ) {
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
		}

		$result = array_merge( $result, $this->buildNativeRates( $otherMethods, $package, $product->get_shipping_class_id() ) );

		if ( empty( $result ) ) {
			wp_send_json_error( array( 'message' => __( 'Frete não disponível para este endereço.', 'melhor-envio-cotacao' ) ) );
		}

		usort( $result, static fn( $a, $b ) => $a['price'] <=> $b['price'] );

		wp_send_json_success( $result );
	}

	/**
	 * Builds quote rows for the WooCommerce native shipping methods configured
	 * in the matching zone (flat rate, free shipping, local pickup, etc.),
	 * so they show up alongside the Melhor Envio quotations.
	 *
	 * @param \WC_Shipping_Method[] $methods
	 * @param array                 $package
	 * @param int                   $productShippingClassId
	 * @return array
	 */
	private function buildNativeRates( array $methods, array $package, int $productShippingClassId ): array {
		$rates = array();

		foreach ( $methods as $method ) {
			$shippingClassId = (int) ( $method->instance_settings['shipping_class_id'] ?? 0 );

			if ( $productShippingClassId && $shippingClassId > 0 && $shippingClassId !== $productShippingClassId ) {
				continue;
			}

			if ( $method->id === 'free_shipping' ) {
				// Frete grátis nativo do WC é validado contra o total do carrinho,
				// que na página de produto normalmente está vazio; por isso ele é
				// sempre exibido aqui (exceto quando exige só cupom, que não dá pra
				// validar fora do carrinho), com a condição explicada na observação.
				if ( $method->requires === 'coupon' ) {
					continue;
				}

				$rates[] = array(
					'name'          => $method->title,
					'company'       => '',
					'price'         => 0.0,
					'delivery_time' => 0,
					'observation'   => $this->freeShippingObservation( $method ),
				);

				continue;
			}

			$methodRates = $method->get_rates_for_package( $package );
			$rate        = end( $methodRates );

			if ( empty( $rate ) ) {
				continue;
			}

			$rates[] = array(
				'name'          => $method->title,
				'company'       => '',
				'price'         => (float) $rate->get_cost(),
				'delivery_time' => 0,
			);
		}

		return $rates;
	}

	/**
	 * Explica a condição do frete grátis (valor mínimo e/ou cupom), já que a
	 * cotação da página de produto não tem como validar isso sem o carrinho.
	 */
	private function freeShippingObservation( \WC_Shipping_Method $method ): string {
		$minAmount = (float) ( $method->min_amount ?? 0 );

		if ( $method->requires === 'both' ) {
			return sprintf(
				/* translators: %s: minimum order amount, e.g. R$10,00 */
				__( 'Frete grátis para pedidos com valor mínimo de %s e uso de cupom.', 'melhor-envio-cotacao' ),
				$this->formatPrice( $minAmount )
			);
		}

		if ( $method->requires === 'either' ) {
			return $minAmount > 0
				? sprintf(
					/* translators: %s: minimum order amount, e.g. R$10,00 */
					__( 'Frete grátis para pedidos com valor mínimo de %s ou uso de cupom.', 'melhor-envio-cotacao' ),
					$this->formatPrice( $minAmount )
				)
				: __( 'Frete grátis mediante uso de cupom.', 'melhor-envio-cotacao' );
		}

		if ( $method->requires === 'min_amount' && $minAmount > 0 ) {
			return sprintf(
				/* translators: %s: minimum order amount, e.g. R$10,00 */
				__( 'Frete grátis para pedidos com valor mínimo de %s.', 'melhor-envio-cotacao' ),
				$this->formatPrice( $minAmount )
			);
		}

		return '';
	}

	private function formatPrice( float $value ): string {
		return 'R$' . number_format( $value, 2, ',', '.' );
	}
}
