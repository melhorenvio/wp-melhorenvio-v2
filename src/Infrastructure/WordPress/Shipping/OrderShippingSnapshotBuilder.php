<?php

declare(strict_types=1);

namespace MelhorEnvio\Infrastructure\WordPress\Shipping;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class OrderShippingSnapshotBuilder {

	public const META_KEY = 'melhor_integrador_order_data';

	private OrderItemsBuilder $orderItemsBuilder;

	public function __construct( OrderItemsBuilder $orderItemsBuilder ) {
		$this->orderItemsBuilder = $orderItemsBuilder;
	}

	public function buildSnapshot( \WC_Order $order ): array {
		return array_merge(
			$this->buildServiceData( $order ),
			array(
				'date_quotation' => current_time( 'mysql' ),
				'products'       => $this->buildProducts( $order, $this->orderItemsBuilder->buildItems( $order ) ),
			)
		);
	}

	private function buildServiceData( \WC_Order $order ): array {
		$empty = array(
			'service_id'    => null,
			'service_name'  => '',
			'company_id'    => 0,
			'company_name'  => '',
			'price'         => '',
			'delivery_time' => 0,
		);

		$shippingItems = $order->get_items( 'shipping' );

		if ( empty( $shippingItems ) ) {
			return $empty;
		}

		$shippingItem = reset( $shippingItems );

		if ( $shippingItem->get_method_id() !== 'melhor_envio' ) {
			return $empty;
		}

		$serviceId = (int) $shippingItem->get_meta( 'me_service_id', true );

		if ( ! $serviceId ) {
			return $empty;
		}

		return array(
			'service_id'    => $serviceId,
			'service_name'  => (string) $shippingItem->get_meta( 'me_service_name', true ),
			'company_id'    => (int) $shippingItem->get_meta( 'me_company_id', true ),
			'company_name'  => (string) $shippingItem->get_meta( 'me_company_name', true ),
			'price'         => (string) $shippingItem->get_total(),
			'delivery_time' => (int) $shippingItem->get_meta( 'me_delivery_time', true ),
		);
	}

	private function buildProducts( \WC_Order $order, array $items ): array {
		$lineItemsByProductId = array();

		foreach ( $order->get_items() as $lineItem ) {
			$key                          = $lineItem->get_variation_id() ?: $lineItem->get_product_id();
			$lineItemsByProductId[ $key ] = $lineItem;
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
