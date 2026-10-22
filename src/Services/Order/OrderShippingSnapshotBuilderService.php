<?php

declare(strict_types=1);

namespace MelhorEnvio\Services\Order;

use MelhorEnvio\Http\Controllers\Order\OrderInvoiceKeyMetaBoxController;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class OrderShippingSnapshotBuilderService {

	public const META_KEY = 'melhor_integrador_order_data';

	/**
	 * 2.x shipping method id => [service id, service name, company id, company name], named as in
	 * GET /api/v2/me/shipment/services. Discontinued services are left out on purpose.
	 */
	private const LEGACY_SERVICES = array(
		'melhorenvio_correios_pac'               => array( 1, 'PAC', 1, 'Correios' ),
		'melhorenvio_correios_sedex'             => array( 2, 'SEDEX', 1, 'Correios' ),
		'melhorenvio_correios_mini'              => array( 17, 'Mini Envios', 1, 'Correios' ),
		'melhorenvio_jadlog_package'             => array( 3, '.Package', 2, 'Jadlog' ),
		'melhorenvio_jadlog_com'                 => array( 4, '.Com', 2, 'Jadlog' ),
		'melhorenvio_jadlog_package_centralized' => array( 27, '.Package Centralizado', 2, 'Jadlog' ),
		'melhorenvio_latam_juntos'               => array( 12, 'éFácil', 6, 'LATAM Cargo' ),
		'melhorenvio_azul_amanha'                => array( 15, 'Expresso', 9, 'Azul Cargo Express' ),
		'melhorenvio_azul_ecommerce'             => array( 16, 'e-commerce', 9, 'Azul Cargo Express' ),
		'melhorenvio_totalexpress_standard'      => array( 35, 'Standard', 8, 'Total Express' ),
		'melhorenvio_buslog_rodoviario'          => array( 22, 'Rodoviário', 12, 'Buslog' ),
		'melhorenvio_loggi_express'              => array( 31, 'Express', 14, 'Loggi' ),
		'melhorenvio_loggi_coleta'               => array( 32, 'Coleta', 14, 'Loggi' ),
		'melhorenvio_jet_standard'               => array( 33, 'Standard', 15, 'JeT' ),
	);

	private OrderItemsBuilderService $orderItemsBuilder;

	public function __construct( OrderItemsBuilderService $orderItemsBuilder ) {
		$this->orderItemsBuilder = $orderItemsBuilder;
	}

	public function buildSnapshot( \WC_Order $order ): array {
		return array_merge(
			$this->buildServiceData( $order ),
			$this->buildOrderStatus( $order ),
			array(
				'date_quotation' => current_time( 'mysql' ),
				'invoice_key'       => (string) $order->get_meta( OrderInvoiceKeyMetaBoxController::META_KEY, true ),
				'invoice_xml_danfe' => (string) $order->get_meta( '_me_invoice_xml_danfe', true ),
				'products'       => $this->buildProducts( $order, $this->orderItemsBuilder->buildItems( $order ) ),
			)
		);
	}

	private function buildOrderStatus( \WC_Order $order ): array {
		$status = $order->get_meta( 'melhorenvio_status_v2', true );

		$purchase = null;

		if ( is_array( $status ) && ! empty( $status['order_id'] ) ) {
			$purchase = array(
				'order_id'    => $status['order_id'],
				'purchase_id' => $status['purchase_id'] ?? null,
				'protocol'    => $status['protocol'] ?? null,
				'status'      => $status['status'] ?? null,
			);
		}

		return array( 'me_purchase' => $purchase );
	}

	private function buildServiceData( \WC_Order $order ): array {
		$shippingItems = $order->get_items( 'shipping' );
		$shippingItem  = reset( $shippingItems );
		$serviceData   = null;

		if ( $shippingItem ) {
			$serviceData = $shippingItem->get_method_id() === 'melhor_envio'
				? $this->buildMelhorEnvioServiceData( $shippingItem )
				: $this->buildLegacyServiceData( $shippingItem );
		}

		return $serviceData ?? array(
			'service_id'    => null,
			'service_name'  => '',
			'company_id'    => 0,
			'company_name'  => '',
			'price'         => '',
			'delivery_time' => 0,
		);
	}

	private function buildMelhorEnvioServiceData( \WC_Order_Item_Shipping $shippingItem ): ?array {
		$serviceId = (int) $shippingItem->get_meta( 'me_service_id', true );

		if ( ! $serviceId ) {
			return null;
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

	/**
	 * Orders placed with the 2.x per-service methods (melhorenvio_*) carry no me_* meta, only the
	 * delivery_time label ("(13 a 15 dias úteis)"), so the service is resolved from the method id.
	 */
	private function buildLegacyServiceData( \WC_Order_Item_Shipping $shippingItem ): ?array {
		$service = self::LEGACY_SERVICES[ $shippingItem->get_method_id() ] ?? null;

		if ( $service === null ) {
			return null;
		}

		preg_match_all( '/\d+/', (string) $shippingItem->get_meta( 'delivery_time', true ), $days );

		return array(
			'service_id'    => $service[0],
			'service_name'  => $service[1],
			'company_id'    => $service[2],
			'company_name'  => $service[3],
			'price'         => (string) $shippingItem->get_total(),
			'delivery_time' => $days[0] ? (int) max( $days[0] ) : 0,
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
