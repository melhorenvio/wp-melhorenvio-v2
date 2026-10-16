<?php

declare(strict_types=1);

namespace MelhorEnvio\Http\Controllers\Order;

use MelhorEnvio\Http\Controllers\Contracts\ControllerInterface;
use MelhorEnvio\Support\OrderListScreen;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Aviso na listagem de pedidos com o resultado da sincronização na Melhor Envio
 * (ver OrderBulkImportController, que redireciona com os query args do resultado).
 */
final class OrderBulkImportNoticeController implements ControllerInterface {

	public function register(): void {
		add_action( 'admin_notices', array( $this, 'renderNotice' ) );
		add_filter( 'removable_query_args', array( $this, 'addRemovableQueryArgs' ) );
	}

	/**
	 * O WP limpa esses args da URL no navegador, evitando repetir o aviso ao recarregar.
	 */
	public function addRemovableQueryArgs( array $args ): array {
		$args[] = OrderBulkImportController::SENT_QUERY_ARG;
		$args[] = OrderBulkImportController::SKIPPED_QUERY_ARG;
		$args[] = OrderBulkImportController::ERROR_QUERY_ARG;

		return $args;
	}

	public function renderNotice(): void {
		if ( ! OrderListScreen::isCurrent() ) {
			return;
		}

		// Somente leitura do resultado da ação (o nonce foi validado no envio).
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET[ OrderBulkImportController::SENT_QUERY_ARG ] ) ) {
			$sent    = absint( wp_unslash( $_GET[ OrderBulkImportController::SENT_QUERY_ARG ] ) );
			$skipped = absint( wp_unslash( $_GET[ OrderBulkImportController::SKIPPED_QUERY_ARG ] ?? 0 ) );
			$this->printNotice( 'success', $this->sentMessage( $sent, $skipped ) );
			return;
		}

		$code     = sanitize_key( wp_unslash( $_GET[ OrderBulkImportController::ERROR_QUERY_ARG ] ?? '' ) );
		$messages = $this->errorMessages();
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( isset( $messages[ $code ] ) ) {
			$this->printNotice( 'error', $messages[ $code ] );
		}
	}

	private function sentMessage( int $sent, int $skipped ): string {
		$message = sprintf(
			/* translators: %d: quantidade de pedidos enviados. */
			__( '%d pedido(s) enviado(s) para sincronização na Melhor Envio. O resultado aparece nas notas de cada pedido.', 'melhor-envio-cotacao' ),
			$sent
		);

		if ( $skipped > 0 ) {
			$message .= ' ' . sprintf(
				/* translators: %d: quantidade de pedidos ignorados. */
				__( '%d pedido(s) concluído(s) ou cancelado(s) foram ignorados.', 'melhor-envio-cotacao' ),
				$skipped
			);
		}

		return $message;
	}

	private function errorMessages(): array {
		return array(
			'no_webhook'     => __( 'O webhook da Melhor Envio não está configurado ou está inativo nesta loja. Reconecte a integração na Melhor Envio.', 'melhor-envio-cotacao' ),
			'limit'          => sprintf(
				/* translators: %d: quantidade máxima de pedidos por envio. */
				__( 'Selecione no máximo %d pedidos por vez.', 'melhor-envio-cotacao' ),
				OrderBulkImportController::MAX_ORDERS
			),
			'throttled'      => __( 'Aguarde alguns segundos antes de enviar uma nova sincronização.', 'melhor-envio-cotacao' ),
			'empty'          => __( 'Selecione ao menos um pedido.', 'melhor-envio-cotacao' ),
			'not_found'      => __( 'Nenhum pedido válido foi encontrado para sincronização.', 'melhor-envio-cotacao' ),
			'blocked_status' => __( 'Pedidos concluídos ou cancelados não podem ser sincronizados na Melhor Envio.', 'melhor-envio-cotacao' ),
		);
	}

	private function printNotice( string $type, string $message ): void {
		printf(
			'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( $type ),
			esc_html( $message )
		);
	}
}
