<?php

declare(strict_types=1);

namespace MelhorEnvio\Services\Order;

/**
 * Grava metadados de controle do plugin sem "atualizar" o pedido.
 *
 * Com HPOS, save_meta_data() salva o pedido inteiro após cada mudança de meta
 * (OrdersTableDataStore::after_meta_change): atualiza date_modified e dispara
 * woocommerce_update_order, o que gera webhooks order.updated para todos os
 * consumidores (MI, ERPs) sem mudança real. O filtro abaixo desliga esse save só
 * durante a gravação — o mesmo padrão que o próprio WooCommerce usa no backfill.
 * A meta continua persistida, o cache do pedido é limpo e, com a sincronização
 * de compatibilidade ativa, ela é copiada para a tabela de posts. Sem HPOS ou com
 * WooCommerce < 8.8 o filtro não existe e nada muda.
 */
final class OrderMetaPersister {

	private const FILTER = 'woocommerce_orders_table_datastore_should_save_after_meta_change';

	public static function saveWithoutTouchingOrder( \WC_Order $order ): void {
		// Closure própria: remove_filter('__return_false') removeria o de outro plugin.
		$skipSave = static function (): bool {
			return false;
		};

		add_filter( self::FILTER, $skipSave );

		try {
			$order->save_meta_data();
		} finally {
			remove_filter( self::FILTER, $skipSave );
		}
	}
}
