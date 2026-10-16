<?php

declare(strict_types=1);

namespace MelhorEnvio\Http\Controllers\Order;

use MelhorEnvio\Http\Controllers\Contracts\ControllerInterface;
use MelhorEnvio\Services\Admin\PluginModeService;
use MelhorEnvio\Services\Auth\SecretService;
use MelhorEnvio\Services\Order\ForceImportService;
use MelhorEnvio\Services\Webhook\MelhorIntegradorWebhookService;
use MelhorEnvio\Support\OrderListScreen;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ação em massa "Sincronizar na Melhor Envio" na listagem de pedidos, mais um botão
 * por pedido (coluna própria, só ícone) para importar um único pedido.
 *
 * O plugin não monta nem assina payload: marca os pedidos para importação forçada
 * e pede ao WooCommerce para entregar o webhook do MI desses pedidos (a marca segue
 * no meta_data do payload, ver OrderMetaBackfillController).
 */
final class OrderBulkImportController implements ControllerInterface {

	public const ACTION           = 'melhor_integrador_import';
	public const SINGLE_ACTION    = 'melhor_integrador_import_order';
	public const COLUMN           = 'melhor_envio_import';
	public const MAX_ORDERS       = 50;
	public const THROTTLE_SECONDS = 30;

	public const SENT_QUERY_ARG    = 'me_mi_import_sent';
	public const SKIPPED_QUERY_ARG = 'me_mi_import_skipped';
	public const ERROR_QUERY_ARG   = 'me_mi_import_error';

	// Pedido concluído ou cancelado nunca é importado, nem forçado (o MI também barra).
	private const BLOCKED_STATUSES = array( 'completed', 'cancelled' );

	private const LOCK_TRANSIENT_PREFIX = 'melhor_integrador_import_lock_';

	private SecretService $secretService;

	private MelhorIntegradorWebhookService $webhookService;

	private ForceImportService $forceImport;

	// isAvailable() roda várias vezes por carregamento da lista e consulta todos os webhooks.
	private ?bool $available = null;

	public function __construct(
		SecretService $secretService,
		MelhorIntegradorWebhookService $webhookService,
		ForceImportService $forceImport
	) {
		$this->secretService  = $secretService;
		$this->webhookService = $webhookService;
		$this->forceImport    = $forceImport;
	}

	public function register(): void {
		foreach ( array_keys( OrderListScreen::SCREENS ) as $screen ) {
			add_filter( 'bulk_actions-' . $screen, array( $this, 'addBulkAction' ) );
			add_filter( 'handle_bulk_actions-' . $screen, array( $this, 'handleBulkAction' ), 10, 3 );
		}

		// Coluna própria: a coluna "Ações" do WC vem oculta por padrão nas duas telas.
		add_filter( 'manage_edit-shop_order_columns', array( $this, 'addColumn' ) );
		add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'addColumn' ) );
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'renderLegacyColumn' ), 10, 2 );
		add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'renderHposColumn' ), 10, 2 );
		add_action( 'admin_post_' . self::SINGLE_ACTION, array( $this, 'handleSingleImport' ) );

		add_action( 'admin_head', array( $this, 'renderColumnStyle' ) );
		add_action( 'admin_footer', array( $this, 'renderSubmitGuard' ) );
	}

	public function isAvailable(): bool {
		if ( $this->available === null ) {
			$this->available = PluginModeService::isIntegradorMode()
				&& $this->secretService->getSecret() !== null
				&& $this->webhookService->hasAny();
		}

		return $this->available;
	}

	public function addBulkAction( array $actions ): array {
		if ( $this->isAvailable() ) {
			$actions[ self::ACTION ] = __( 'Sincronizar na Melhor Envio', 'melhor-envio-cotacao' );
		}

		return $actions;
	}

	/**
	 * @param string|false $redirectTo
	 * @param string       $action
	 * @param array        $ids
	 * @return string|false
	 */
	public function handleBulkAction( $redirectTo, $action, $ids ) {
		if ( self::ACTION !== $action ) {
			return $redirectTo;
		}

		check_admin_referer( OrderListScreen::bulkNonceAction() );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Você não tem permissão para sincronizar pedidos.', 'melhor-envio-cotacao' ) );
		}

		return $this->run( (array) $ids, (string) $redirectTo, $this->lockKey() );
	}

	public function addColumn( array $columns ): array {
		if ( $this->isAvailable() ) {
			$columns[ self::COLUMN ] = __( 'Melhor Envio', 'melhor-envio-cotacao' );
		}

		return $columns;
	}

	/**
	 * @param string $column
	 * @param int    $postId
	 */
	public function renderLegacyColumn( $column, $postId ): void {
		if ( self::COLUMN !== $column ) {
			return;
		}

		$order = wc_get_order( (int) $postId );

		if ( $order instanceof \WC_Order ) {
			$this->renderImportButton( $order );
		}
	}

	/**
	 * @param string    $column
	 * @param \WC_Order $order
	 */
	public function renderHposColumn( $column, $order ): void {
		if ( self::COLUMN === $column && $order instanceof \WC_Order ) {
			$this->renderImportButton( $order );
		}
	}

	/**
	 * Botão da coluna (admin-post): importa um único pedido com as mesmas regras da ação em massa.
	 */
	public function handleSingleImport(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce validado logo abaixo, por pedido.
		$orderId = absint( wp_unslash( $_GET['order_id'] ?? 0 ) );

		check_admin_referer( self::SINGLE_ACTION . '_' . $orderId );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Você não tem permissão para sincronizar pedidos.', 'melhor-envio-cotacao' ) );
		}

		$redirectTo = wp_get_referer() ?: admin_url( 'edit.php?post_type=shop_order' );

		// Trava por pedido: o lojista pode importar pedidos diferentes em sequência.
		wp_safe_redirect( $this->run( array( $orderId ), (string) $redirectTo, $this->lockKey( $orderId ) ) );
		exit;
	}

	public function renderColumnStyle(): void {
		if ( ! OrderListScreen::isCurrent() ) {
			return;
		}
		?>
		<style>
			.column-<?php echo esc_attr( self::COLUMN ); ?> { width: 7em; text-align: center !important; }
			.me-import-order-button .dashicons { line-height: inherit; }
			.me-import-order-button.me-import-sending { pointer-events: none; }
		</style>
		<?php
	}

	/**
	 * Evita envio duplo: desabilita os botões "Aplicar" ao submeter a nossa ação.
	 */
	public function renderSubmitGuard(): void {
		if ( ! OrderListScreen::isCurrent() || ! $this->isAvailable() ) {
			return;
		}
		?>
		<script>
		(function () {
			document.addEventListener('click', function (event) {
				var button = event.target.closest ? event.target.closest('.me-import-order-button') : null;
				if (button && !button.classList.contains('disabled')) button.classList.add('disabled', 'me-import-sending');
			});

			var action = '<?php echo esc_js( self::ACTION ); ?>';
			var label  = '<?php echo esc_js( __( 'Enviando…', 'melhor-envio-cotacao' ) ); ?>';
			var top    = document.getElementById('bulk-action-selector-top');
			var bottom = document.getElementById('bulk-action-selector-bottom');
			var form   = (top || bottom) ? (top || bottom).form : null;
			if (!form) return;
			form.addEventListener('submit', function () {
				// Mesma regra do WP_List_Table::current_action(): seletor de cima, senão o de baixo.
				var selected = top && top.value !== '-1' ? top.value : (bottom ? bottom.value : '');
				if (selected !== action) return;
				['doaction', 'doaction2'].forEach(function (id) {
					var button = document.getElementById(id);
					if (!button) return;
					button.disabled = true;
					button.value    = label;
				});
			});
		})();
		</script>
		<?php
	}

	private function run( array $ids, string $redirectTo, string $lockKey ): string {
		$redirectTo = remove_query_arg( array( self::SENT_QUERY_ARG, self::SKIPPED_QUERY_ARG, self::ERROR_QUERY_ARG ), $redirectTo );
		$error      = $this->validate( $ids, $lockKey );

		if ( $error !== null ) {
			return add_query_arg( self::ERROR_QUERY_ARG, $error, $redirectTo );
		}

		list( $sent, $skipped ) = $this->import( $ids );

		if ( $sent === 0 ) {
			return add_query_arg( self::ERROR_QUERY_ARG, $skipped > 0 ? 'blocked_status' : 'not_found', $redirectTo );
		}

		$redirectTo = add_query_arg( self::SENT_QUERY_ARG, $sent, $redirectTo );

		return $skipped > 0 ? add_query_arg( self::SKIPPED_QUERY_ARG, $skipped, $redirectTo ) : $redirectTo;
	}

	private function lockKey( int $orderId = 0 ): string {
		$key = self::LOCK_TRANSIENT_PREFIX . get_current_user_id();

		return $orderId > 0 ? $key . '_' . $orderId : $key;
	}

	private function renderImportButton( \WC_Order $order ): void {
		// Concluído/cancelado: botão cinza, sem ação; o tooltip explica o motivo.
		// "no-link" impede a lista legada de abrir o pedido ao clicar na linha.
		if ( $order->has_status( self::BLOCKED_STATUSES ) ) {
			printf(
				'<span class="button disabled no-link me-import-order-button" aria-disabled="true" title="%1$s" aria-label="%1$s"><span class="dashicons dashicons-update" aria-hidden="true"></span></span>',
				esc_attr__( 'Pedidos concluídos ou cancelados não podem ser sincronizados na Melhor Envio.', 'melhor-envio-cotacao' )
			);
			return;
		}

		$orderId = $order->get_id();
		$label   = __( 'Sincronizar na Melhor Envio', 'melhor-envio-cotacao' );
		$url   = wp_nonce_url(
			admin_url( 'admin-post.php?action=' . self::SINGLE_ACTION . '&order_id=' . $orderId ),
			self::SINGLE_ACTION . '_' . $orderId
		);

		printf(
			'<a class="button me-import-order-button" href="%1$s" title="%2$s" aria-label="%2$s"><span class="dashicons dashicons-update" aria-hidden="true"></span></a>',
			esc_url( $url ),
			esc_attr( $label )
		);
	}

	private function validate( array $ids, string $lockKey ): ?string {
		$error = null;

		if ( get_transient( $lockKey ) ) {
			$error = 'throttled';
		} elseif ( empty( $ids ) ) {
			$error = 'empty';
		} elseif ( count( $ids ) > self::MAX_ORDERS ) {
			$error = 'limit';
		} elseif ( empty( $this->webhookService->getDeliverableWebhooks() ) ) {
			$error = 'no_webhook';
		} else {
			set_transient( $lockKey, 1, self::THROTTLE_SECONDS );
		}

		return $error;
	}

	/**
	 * @return int[] Pedidos enviados e pedidos ignorados por status (concluído/cancelado).
	 */
	private function import( array $ids ): array {
		$webhooks = $this->webhookService->getDeliverableWebhooks();
		$sent     = 0;
		$skipped  = 0;

		foreach ( $ids as $id ) {
			$order = wc_get_order( absint( $id ) );

			if ( ! $order instanceof \WC_Order ) {
				continue;
			}

			if ( $order->has_status( self::BLOCKED_STATUSES ) ) {
				$order->add_order_note( __( 'Sincronização na Melhor Envio não realizada: pedidos concluídos ou cancelados não podem ser sincronizados.', 'melhor-envio-cotacao' ) );
				++$skipped;
				continue;
			}

			$this->forceImport->mark( $order );

			// process() passa pelas validações do WC e pelo filtro woocommerce_webhook_should_deliver,
			// e enfileira a entrega (assíncrona) — ao contrário de deliver(), que envia direto.
			foreach ( $webhooks as $webhook ) {
				$webhook->process( $order->get_id() );
			}

			$order->add_order_note( __( 'Sincronização na Melhor Envio solicitada.', 'melhor-envio-cotacao' ) );
			++$sent;
		}

		return array( $sent, $skipped );
	}
}
