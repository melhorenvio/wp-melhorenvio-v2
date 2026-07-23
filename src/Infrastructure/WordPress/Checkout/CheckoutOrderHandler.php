<?php

declare(strict_types=1);

namespace MelhorEnvio\Infrastructure\WordPress\Checkout;

use MelhorEnvio\Infrastructure\WordPress\Shipping\CartItemsBuilder;
use MelhorEnvio\Infrastructure\WordPress\Shipping\UnitConverter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CheckoutOrderHandler {

	public const META_KEY = 'melhor_integrador_order_data';

	private CartItemsBuilder $cartItemsBuilder;

	public function __construct( CartItemsBuilder $cartItemsBuilder ) {
		$this->cartItemsBuilder = $cartItemsBuilder;
	}

	public function register(): void {
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'saveOrderData' ) );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'saveOrderDataFromOrder' ) );
	}

	public function saveOrderData( int $orderId ): void {
		$order = wc_get_order( $orderId );

		if ( ! $order ) {
			return;
		}

		$this->process( $order );
	}

	public function saveOrderDataFromOrder( \WC_Order $order ): void {
		$this->process( $order );
	}

	private function process( \WC_Order $order ): void {
		$shippingItems = $order->get_items( 'shipping' );

		if ( empty( $shippingItems ) ) {
			return;
		}

		$shippingItem = reset( $shippingItems );

		if ( $shippingItem->get_method_id() !== 'melhor_envio' ) {
			return;
		}

		$serviceId = (int) $shippingItem->get_meta( 'me_service_id' );

		if ( ! $serviceId ) {
			return;
		}

		$service  = $this->getServiceFromTransient( $order->get_shipping_postcode(), $serviceId );
		$products = $this->buildProducts( $order );

		$data = array(
			'service_id'     => $serviceId,
			'service_name'   => $service['name'] ?? '',
			'company_id'     => (int) ( $service['company']['id'] ?? 0 ),
			'company_name'   => $service['company']['name'] ?? '',
			'price'          => $service['price'] ?? (string) $shippingItem->get_total(),
			'delivery_time'  => (int) ( $service['delivery_time'] ?? 0 ),
			'date_quotation' => current_time( 'mysql' ),
			'products'       => $products,
		);

		$order->update_meta_data( self::META_KEY, $data );
		$order->save_meta_data();
	}

	private function getServiceFromTransient( string $postcode, int $serviceId ): array {
		$toCep    = preg_replace( '/\D/', '', $postcode );
		$items    = $this->cartItemsBuilder->buildItems();
		$cacheKey = 'me_quote_' . md5( $toCep . serialize( $items ) );
		$cached   = get_transient( $cacheKey );

		return ( is_array( $cached ) && isset( $cached[ $serviceId ] ) )
			? $cached[ $serviceId ]
			: array();
	}

	private function buildProducts( \WC_Order $order ): array {
		$products = array();

		foreach ( $order->get_items() as $lineItem ) {
			$product = $lineItem->get_product();

			if ( ! $product ) {
				continue;
			}

			$qty        = max( 1, (int) $lineItem->get_quantity() );
			$unitPrice  = (float) $lineItem->get_subtotal() / $qty;

			$products[] = array(
				'id'              => $lineItem->get_id(),
				'product_id'      => $lineItem->get_product_id(),
				'name'            => $lineItem->get_name(),
				'quantity'        => $qty,
				'price'           => $unitPrice,
				'weight'          => UnitConverter::toKg( (float) ( $product->get_weight() ?: 0.3 ) ),
				'height'          => (int) round( UnitConverter::toCm( (float) ( $product->get_height() ?: 2 ) ) ),
				'width'           => (int) round( UnitConverter::toCm( (float) ( $product->get_width() ?: 11 ) ) ),
				'length'          => (int) round( UnitConverter::toCm( (float) ( $product->get_length() ?: 16 ) ) ),
				'insurance_value' => $unitPrice,
			);
		}

		return $products;
	}
}
