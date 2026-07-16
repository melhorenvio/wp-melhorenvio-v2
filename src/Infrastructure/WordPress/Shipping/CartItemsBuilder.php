<?php

declare(strict_types=1);

namespace MelhorEnvio\Infrastructure\WordPress\Shipping;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Monta os itens de cotação a partir do carrinho, decompondo kits (WPC Product Bundles)
 * e composições (WPC Composite Products) em seus componentes reais quando aplicável.
 */
final class CartItemsBuilder {

	private const BUNDLE_TYPES    = array( 'woosb', 'product-woosb' );
	private const COMPOSITE_TYPES = array( 'composite', 'product-composite' );

	public static function isComposedProduct( \WC_Product $product ): bool {
		return self::isBundleProduct( $product ) || self::isCompositeProduct( $product );
	}

	public static function isBundleProduct( \WC_Product $product ): bool {
		return in_array( $product->get_type(), self::BUNDLE_TYPES, true );
	}

	public static function isCompositeProduct( \WC_Product $product ): bool {
		return in_array( $product->get_type(), self::COMPOSITE_TYPES, true );
	}

	/**
	 * Um bundle com item opcional pode ter composição diferente da configuração padrão
	 * depois que o cliente desmarcar algo - por isso a calculadora da página de produto
	 * só deve confiar na composição padrão quando não há itens opcionais, ou quando a
	 * seleção viva do formulário foi informada (ver `buildItemsForBundleProduct`).
	 */
	public static function bundleHasOptionalItems( \WC_Product $product ): bool {
		return method_exists( $product, 'has_optional' ) && $product->has_optional();
	}

	/**
	 * Monta os itens de cotação para um produto bundle (WPC Product Bundles) fora do
	 * carrinho, direto da definição do produto ('woosb_ids'/`get_items()`).
	 *
	 * @param string|null $selectionIds Valor cru do campo `woosb_ids` do formulário da
	 *                                  página de produto (reflete a seleção viva de itens
	 *                                  opcionais/quantidades). Quando informado, o próprio
	 *                                  `WC_Product_Woosb::build_items()` resolve a partir
	 *                                  dele em vez da composição padrão do produto.
	 */
	public function buildItemsForBundleProduct( \WC_Product $product, int $quantity, ?string $selectionIds = null ): array {
		if ( ! self::isBundleProduct( $product ) || ! method_exists( $product, 'get_items' ) ) {
			return array( $this->toApiItem( $this->toLine( $product, $quantity, (float) $product->get_price() ) ) );
		}

		if ( ! empty( $selectionIds ) && method_exists( $product, 'build_items' ) ) {
			$product->build_items( $selectionIds );
		}

		$shippingFee = get_post_meta( $product->get_id(), 'woosb_shipping_fee', true );

		// Fora do carrinho (sem passar por 'woocommerce_before_calculate_totals'), o kit com
		// preço automático não tem '_price' preenchido - só get_regular_price() soma os
		// componentes nesse caso. Preço fixo já retorna certo via get_price().
		$kitUnitPrice  = (float) $product->get_price() ?: (float) $product->get_regular_price();
		$kitPriceTotal = $kitUnitPrice * $quantity;

		$components = array();

		foreach ( $product->get_items() as $componentDef ) {
			$componentProduct = wc_get_product( $componentDef['id'] ?? 0 );

			if ( ! $componentProduct instanceof \WC_Product ) {
				continue;
			}

			$componentQty = max( 1, (int) ( $componentDef['qty'] ?? 1 ) ) * $quantity;

			$components[] = $this->toLine( $componentProduct, $componentQty, (float) $componentProduct->get_price() );
		}

		$dumpIntoFirst = method_exists( $product, 'is_fixed_price' ) && $product->is_fixed_price();

		return $this->composedItems( $product, $quantity, $kitPriceTotal, $shippingFee, $components, $dumpIntoFirst );
	}

	/**
	 * Monta os itens de cotação para um produto composição (WPC Composite Products) fora
	 * do carrinho. Diferente do bundle, a composição não tem uma "composição padrão"
	 * significativa - só é possível cotar com a seleção viva do formulário da página de
	 * produto, resolvida via `WPCleverWooco_Helper::get_items()`.
	 *
	 * @param string $selectionIds Valor cru do campo `wooco_ids` do formulário.
	 * @return array<int, array<string, mixed>>|null `null` quando não há seleção
	 *                                                suficiente para cotar.
	 */
	public function buildItemsForCompositeProduct( \WC_Product $product, int $quantity, string $selectionIds ): ?array {
		if ( ! self::isCompositeProduct( $product ) || empty( $selectionIds ) || ! class_exists( '\WPCleverWooco_Helper' ) ) {
			return null;
		}

		$resolved = \WPCleverWooco_Helper::get_items( $selectionIds );

		$components = array();

		foreach ( $resolved as $componentDef ) {
			$componentProduct = wc_get_product( $componentDef['id'] ?? 0 );

			if ( ! $componentProduct instanceof \WC_Product ) {
				continue;
			}

			$componentQty = max( 1, (int) ( $componentDef['qty'] ?? 1 ) ) * $quantity;

			$components[] = $this->toLine( $componentProduct, $componentQty, (float) $componentProduct->get_price() );
		}

		if ( empty( $components ) ) {
			return null;
		}

		$shippingFee = get_post_meta( $product->get_id(), 'wooco_shipping_fee', true );
		$pricing     = get_post_meta( $product->get_id(), 'wooco_pricing', true );

		$parentBaseTotal = (float) $product->get_price() * $quantity;
		$componentsTotal = array_sum(
			array_map(
				static fn( array $component ): float => $component['unitaryValue'] * $component['quantity'],
				$components
			)
		);

		$aggregateTotal = match ( $pricing ) {
			'exclude' => $componentsTotal,
			'only'    => $parentBaseTotal,
			default   => $parentBaseTotal + $componentsTotal, // 'include' ou não configurado.
		};

		$dumpIntoFirst = in_array( $pricing, array( 'include', 'only' ), true );

		return $this->composedItems( $product, $quantity, $aggregateTotal, $shippingFee, $components, $dumpIntoFirst );
	}

	public function buildItems(): array {
		$cartItems = WC()->cart->get_cart();
		$items     = array();

		foreach ( $cartItems as $item ) {
			$product = $item['data'] ?? null;

			if ( ! $product instanceof \WC_Product ) {
				continue;
			}

			// Filho de um kit/composição: já será expandido a partir da linha pai.
			if ( isset( $item['woosb_parent_id'] ) || isset( $item['wooco_parent_id'] ) ) {
				continue;
			}

			if ( self::isBundleProduct( $product ) && isset( $item['woosb_keys'] ) ) {
				array_push( $items, ...$this->buildBundleCartRow( $item, $cartItems ) );
				continue;
			}

			if ( self::isCompositeProduct( $product ) && isset( $item['wooco_keys'] ) ) {
				array_push( $items, ...$this->buildCompositeCartRow( $item, $cartItems ) );
				continue;
			}

			$items[] = $this->toApiItem( $this->toLine( $product, (int) $item['quantity'], (float) $product->get_price() ) );
		}

		return $items;
	}

	/**
	 * @param array<string, mixed> $item
	 * @param array<string, mixed> $cartItems
	 */
	private function buildBundleCartRow( array $item, array $cartItems ): array {
		$product     = $item['data'];
		$quantity    = (int) $item['quantity'];
		$kitPrice    = ! empty( $item['woosb_price'] ) ? (float) $item['woosb_price'] : (float) $item['line_total'];
		$shippingFee = get_post_meta( $product->get_id(), 'woosb_shipping_fee', true );

		$components    = $this->expandComponents( (array) $item['woosb_keys'], $cartItems );
		$dumpIntoFirst = method_exists( $product, 'is_fixed_price' ) && $product->is_fixed_price();

		return $this->composedItems( $product, $quantity, $kitPrice, $shippingFee, $components, $dumpIntoFirst );
	}

	/**
	 * @param array<string, mixed> $item
	 * @param array<string, mixed> $cartItems
	 */
	private function buildCompositeCartRow( array $item, array $cartItems ): array {
		$product     = $item['data'];
		$quantity    = (int) $item['quantity'];
		$totalPrice  = ! empty( $item['wooco_price'] ) ? (float) $item['wooco_price'] : (float) $item['line_total'];
		$shippingFee = get_post_meta( $product->get_id(), 'wooco_shipping_fee', true );
		$pricing     = get_post_meta( $product->get_id(), 'wooco_pricing', true );

		$components    = $this->expandComponents( (array) $item['wooco_keys'], $cartItems );
		$dumpIntoFirst = in_array( $pricing, array( 'include', 'only' ), true );

		return $this->composedItems( $product, $quantity, $totalPrice, $shippingFee, $components, $dumpIntoFirst );
	}

	/**
	 * Regra comum a bundle e composição, tanto no carrinho quanto na página de produto:
	 * `shipping_fee === 'each'` cota os componentes reais (não o pai); qualquer outro
	 * valor cota só o pai (com as próprias dimensões). `$dumpIntoFirst` cobre os modos em
	 * que o valor real do pai não está distribuído nos componentes (kit com preço fixo,
	 * composição com pricing include/only) - nesse caso o valor total agregado é jogado
	 * no primeiro componente para não perder o seguro.
	 *
	 * @param array<int, array{product: \WC_Product, quantity: int, unitaryValue: float}> $components
	 */
	private function composedItems(
		\WC_Product $parent,
		int $quantity,
		float $aggregateTotal,
		string $shippingFee,
		array $components,
		bool $dumpIntoFirst
	): array {
		if ( $dumpIntoFirst && ! empty( $components ) ) {
			foreach ( $components as $key => $component ) {
				$components[ $key ]['unitaryValue'] = 0.0;
			}

			$components[0]['unitaryValue'] = $aggregateTotal / max( 1, $components[0]['quantity'] );
		}

		if ( $shippingFee === 'each' && ! empty( $components ) ) {
			return array_map( array( $this, 'toApiItem' ), $components );
		}

		return array( $this->toApiItem( $this->toLine( $parent, $quantity, $aggregateTotal / max( 1, $quantity ) ) ) );
	}

	/**
	 * @param array<int, string>   $keys
	 * @param array<string, mixed> $cartItems
	 * @return array<int, array{product: \WC_Product, quantity: int, unitaryValue: float}>
	 */
	private function expandComponents( array $keys, array $cartItems ): array {
		$components = array();

		foreach ( $keys as $key ) {
			if ( ! isset( $cartItems[ $key ] ) ) {
				continue;
			}

			$componentItem = $cartItems[ $key ];
			$componentQty  = (int) $componentItem['quantity'];

			$components[] = $this->toLine(
				$componentItem['data'],
				$componentQty,
				(float) $componentItem['line_total'] / max( 1, $componentQty )
			);
		}

		return $components;
	}

	private function toLine( \WC_Product $product, int $quantity, float $unitaryValue ): array {
		return array(
			'product'      => $product,
			'quantity'     => $quantity,
			'unitaryValue' => $unitaryValue,
		);
	}

	/**
	 * @param array{product: \WC_Product, quantity: int, unitaryValue: float} $line
	 */
	private function toApiItem( array $line ): array {
		$product = $line['product'];

		return array(
			'id'              => $product->get_id(),
			'width'           => (float) ( $product->get_width() ?: 11 ),
			'height'          => (float) ( $product->get_height() ?: 2 ),
			'length'          => (float) ( $product->get_length() ?: 16 ),
			'weight'          => (float) ( $product->get_weight() ?: 0.3 ),
			'insurance_value' => (float) $line['unitaryValue'],
			'quantity'        => $line['quantity'],
		);
	}
}
