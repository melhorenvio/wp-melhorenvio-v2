<?php

declare(strict_types=1);

namespace MelhorEnvio\Services\Order;

use MelhorEnvio\Services\Shipping\CartItemsBuilderService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class OrderItemsBuilderService {

	private CartItemsBuilderService $cartItemsBuilder;

	public function __construct( CartItemsBuilderService $cartItemsBuilder ) {
		$this->cartItemsBuilder = $cartItemsBuilder;
	}

	public function buildItems( \WC_Order $order ): array {
		$orderItems             = array_values( $order->get_items() );
		$childrenByParentItemId = $this->groupChildrenByNearestParent( $orderItems );
		$items                  = array();

		foreach ( $orderItems as $lineItem ) {
			$product = $lineItem->get_product();

			if ( ! $product instanceof \WC_Product ) {
				continue;
			}

			if ( $this->isChildLineItem( $lineItem ) ) {
				continue; // Já será expandido a partir da linha pai correspondente.
			}

			$children = $childrenByParentItemId[ $lineItem->get_id() ] ?? array();

			if ( CartItemsBuilderService::isBundleProduct( $product ) ) {
				array_push( $items, ...$this->buildBundleOrderRow( $lineItem, $product, $children ) );
				continue;
			}

			if ( CartItemsBuilderService::isCompositeProduct( $product ) ) {
				array_push( $items, ...$this->buildCompositeOrderRow( $lineItem, $product, $children ) );
				continue;
			}

			$quantity = max( 1, (int) $lineItem->get_quantity() );
			$items[]  = $this->cartItemsBuilder->toApiItem(
				$this->cartItemsBuilder->toLine( $product, $quantity, (float) $lineItem->get_total() / $quantity )
			);
		}

		return $items;
	}

	private function isChildLineItem( \WC_Order_Item_Product $lineItem ): bool {
		return $lineItem->get_meta( '_woosb_parent_id', true ) !== '' || $lineItem->get_meta( 'wooco_parent_id', true ) !== '';
	}

	/**
	 * @param array<int, \WC_Order_Item_Product> $orderItems
	 * @return array<int, array<int, \WC_Order_Item_Product>> Componentes agrupados pelo
	 *                                                         `get_id()` da linha pai.
	 */
	private function groupChildrenByNearestParent( array $orderItems ): array {
		$lastParentItemIdByProductId = array();
		$childrenByParentItemId      = array();

		foreach ( $orderItems as $item ) {
			$product = $item->get_product();

			if ( $product instanceof \WC_Product && ! $this->isChildLineItem( $item )
				&& ( CartItemsBuilderService::isBundleProduct( $product ) || CartItemsBuilderService::isCompositeProduct( $product ) )
			) {
				$lastParentItemIdByProductId[ $product->get_id() ] = $item->get_id();
				continue;
			}

			$parentProductId = (int) ( $item->get_meta( '_woosb_parent_id', true ) ?: $item->get_meta( 'wooco_parent_id', true ) );

			if ( ! $parentProductId || ! isset( $lastParentItemIdByProductId[ $parentProductId ] ) ) {
				continue;
			}

			$childrenByParentItemId[ $lastParentItemIdByProductId[ $parentProductId ] ][] = $item;
		}

		return $childrenByParentItemId;
	}

	/**
	 * @param array<int, \WC_Order_Item_Product> $children
	 */
	private function buildBundleOrderRow( \WC_Order_Item_Product $lineItem, \WC_Product $product, array $children ): array {
		$quantity    = max( 1, (int) $lineItem->get_quantity() );
		$rawPrice    = $lineItem->get_meta( '_woosb_price', true );
		// Kit com preço fixo não grava '_woosb_price' - o próprio total da linha pai já é
		// o valor real nesse caso (o plugin só zera/desconta o total quando NÃO é fixo).
		$kitPrice    = $rawPrice !== '' ? (float) $rawPrice : (float) $lineItem->get_total();
		$shippingFee = get_post_meta( $product->get_id(), 'woosb_shipping_fee', true );

		$components    = $this->toComponentLines( $children );
		$dumpIntoFirst = method_exists( $product, 'is_fixed_price' ) && $product->is_fixed_price();

		return $this->cartItemsBuilder->composedItems( $product, $quantity, $kitPrice, $shippingFee, $components, $dumpIntoFirst );
	}

	/**
	 * @param array<int, \WC_Order_Item_Product> $children
	 */
	private function buildCompositeOrderRow( \WC_Order_Item_Product $lineItem, \WC_Product $product, array $children ): array {
		$quantity    = max( 1, (int) $lineItem->get_quantity() );
		$rawPrice    = $lineItem->get_meta( 'wooco_price', true );
		$totalPrice  = $rawPrice !== '' ? (float) $rawPrice : (float) $lineItem->get_total();
		$shippingFee = get_post_meta( $product->get_id(), 'wooco_shipping_fee', true );
		$pricing     = get_post_meta( $product->get_id(), 'wooco_pricing', true );

		$components    = $this->toComponentLines( $children );
		$dumpIntoFirst = in_array( $pricing, array( 'include', 'only' ), true );

		return $this->cartItemsBuilder->composedItems( $product, $quantity, $totalPrice, $shippingFee, $components, $dumpIntoFirst );
	}

	/**
	 * @param array<int, \WC_Order_Item_Product> $children
	 * @return array<int, array{product: \WC_Product, quantity: int, unitaryValue: float}>
	 */
	private function toComponentLines( array $children ): array {
		$components = array();

		foreach ( $children as $item ) {
			$product = $item->get_product();

			if ( ! $product instanceof \WC_Product ) {
				continue;
			}

			$quantity = max( 1, (int) $item->get_quantity() );

			$components[] = $this->cartItemsBuilder->toLine( $product, $quantity, (float) $item->get_total() / $quantity );
		}

		return $components;
	}
}
