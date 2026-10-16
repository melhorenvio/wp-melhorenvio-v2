<?php

declare(strict_types=1);

namespace MelhorEnvio\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Telas da listagem de pedidos no admin: posts (legado) e HPOS.
 */
final class OrderListScreen {

	// Id da tela => nonce que o core/WC usa nas ações em massa de cada uma.
	public const SCREENS = array(
		'edit-shop_order'            => 'bulk-posts',
		'woocommerce_page_wc-orders' => 'bulk-orders',
	);

	public static function isCurrent(): bool {
		return isset( self::SCREENS[ self::currentId() ] );
	}

	public static function bulkNonceAction(): string {
		return self::SCREENS[ self::currentId() ] ?? 'bulk-posts';
	}

	private static function currentId(): string {
		$screen = get_current_screen();

		return $screen ? (string) $screen->id : '';
	}
}
