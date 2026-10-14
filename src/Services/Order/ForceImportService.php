<?php

declare(strict_types=1);

namespace MelhorEnvio\Services\Order;

/**
 * Marca pedidos para importação forçada no Melhor Integrador. A marca vai no payload
 * do webhook (via resposta REST do pedido) e faz o MI importar o pedido mesmo fora da
 * janela de dias da importação normal; as demais regras continuam valendo. Uso único:
 * é removida após uma entrega 2xx e expira sozinha após TTL.
 */
final class ForceImportService {

	public const META_KEY    = '_melhor_integrador_force_import';
	public const PAYLOAD_KEY = 'melhor_integrador_force_import';
	public const TTL         = HOUR_IN_SECONDS;

	/**
	 * Usa save_meta_data() em vez de save() para não disparar woocommerce_update_order
	 * (e, com ele, os webhooks order.updated de forma não controlada).
	 */
	public function mark( \WC_Order $order ): void {
		$order->update_meta_data( self::META_KEY, time() + self::TTL );
		$order->save_meta_data();
	}

	public function getActiveExpiration( \WC_Order $order ): ?int {
		$expiresAt = (int) $order->get_meta( self::META_KEY, true );

		if ( $expiresAt <= 0 || $expiresAt < time() ) {
			return null;
		}

		return $expiresAt;
	}

	public function isActive( \WC_Order $order ): bool {
		return $this->getActiveExpiration( $order ) !== null;
	}

	public function clear( \WC_Order $order ): void {
		if ( ! $order->meta_exists( self::META_KEY ) ) {
			return;
		}

		$order->delete_meta_data( self::META_KEY );
		$order->save_meta_data();
	}
}
