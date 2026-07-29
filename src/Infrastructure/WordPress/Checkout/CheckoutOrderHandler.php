<?php

declare(strict_types=1);

namespace MelhorEnvio\Infrastructure\WordPress\Checkout;

use MelhorEnvio\Infrastructure\WordPress\Shipping\CartItemsBuilder;
use MelhorEnvio\Infrastructure\WordPress\Shipping\OrderItemsBuilder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CheckoutOrderHandler {

	public const META_KEY = 'melhor_integrador_order_data';

	private CartItemsBuilder $cartItemsBuilder;
	private OrderItemsBuilder $orderItemsBuilder;

	public function __construct( CartItemsBuilder $cartItemsBuilder, OrderItemsBuilder $orderItemsBuilder ) {
		$this->cartItemsBuilder  = $cartItemsBuilder;
		$this->orderItemsBuilder = $orderItemsBuilder;
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

		$items    = $this->cartItemsBuilder->buildItems();
		$service  = $this->getServiceFromTransient( $order->get_shipping_postcode(), $items, $serviceId );
		$products = $this->buildProducts( $order, $items );

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

	public function buildBackfillSnapshot( \WC_Order $order ): array {
		$items = $this->orderItemsBuilder->buildItems( $order );

		return array(
			'service_id'     => null,
			'service_name'   => '',
			'company_id'     => 0,
			'company_name'   => '',
			'price'          => '',
			'delivery_time'  => 0,
			'date_quotation' => current_time( 'mysql' ),
			'products'       => $this->buildProducts( $order, $items ),
		);
	}

	private function getServiceFromTransient( string $postcode, array $items, int $serviceId ): array {
		$toCep    = preg_replace( '/\D/', '', $postcode );
		$cacheKey = 'me_quote_' . md5( $toCep . serialize( $items ) );
		$cached   = get_transient( $cacheKey );

		if ( ! is_array( $cached ) ) {
			return array();
		}

		foreach ( $cached as $service ) {
			if ( is_array( $service ) && (int) ( $service['id'] ?? 0 ) === $serviceId ) {
				return $service;
			}
		}

		return array();
	}

	private function buildProducts( \WC_Order $order, array $items ): array {
		$lineItemsByProductId = array();

		foreach ( $order->get_items() as $lineItem ) {
			$lineItemsByProductId[ $lineItem->get_product_id() ] = $lineItem;
		}

		$products = array();

		foreach ( $items as $item ) {
			$lineItem = $lineItemsByProductId[ $item['id'] ] ?? null;

			$products[] = array(
				'id'              => $lineItem ? $lineItem->get_id() : $item['id'],
				'product_id'      => $item['id'],
				'name'            => $lineItem ? $lineItem->get_name() : '',
				'quantity'        => max( 1, (int) $item['quantity'] ),
				'price'           => $item['insurance_value'],
				'weight'          => $item['weight'],
				'height'          => (int) round( $item['height'] ),
				'width'           => (int) round( $item['width'] ),
				'length'          => (int) round( $item['length'] ),
				'insurance_value' => $item['insurance_value'],
			);
		}

		return $products;
	}
}
