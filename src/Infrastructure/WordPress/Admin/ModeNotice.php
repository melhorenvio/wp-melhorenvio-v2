<?php

declare(strict_types=1);

namespace MelhorEnvio\Infrastructure\WordPress\Admin;

final class ModeNotice {

	private const ACTION            = 'melhor_envio_set_mode';
	private const NONCE_KEY         = 'melhor_envio_mode_nonce';
	private const DISMISS_META_KEY  = 'melhor_envio_mode_notice_dismissed_at';
	private const DISMISS_DURATION  = DAY_IN_SECONDS;

	public function register(): void {
		add_action( 'admin_notices', array( $this, 'renderNotice' ) );
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handleModeSwitch' ) );
	}

	public function renderNotice(): void {
		if ( PluginModeManager::isIntegradorMode() ) {
			if ( ! $this->isDismissed() ) {
				$this->printStyles();
				$this->renderReturnToLegacyNotice();
			}
		} elseif ( ! $this->isDismissed() ) {
			$this->printStyles();
			$this->renderUpgradeToIntegradorNotice();
		}
	}

	private function isDismissed(): bool {
		$dismissedAt = (int) get_user_meta( get_current_user_id(), self::DISMISS_META_KEY, true );

		return $dismissedAt && ( time() - $dismissedAt ) < self::DISMISS_DURATION;
	}

	private function printStyles(): void {
		?>
		<style>
		.me-alert {
			--me-info: oklch(0.6539 0.1355 243.23);
			--me-primary: oklch(0.4391 0.1409 254.24);
			--me-primary-dark: oklch(0.4008 0.1308 255.49);
			--me-primary-light: oklch(0.5071 0.166498 254.9145);
			--me-white: #fff;
			--me-neutral-bright: oklch(0.971 0.0059 239.82);
			--me-neutral-light: oklch(0.8484 0.0188 269.06);
			--me-neutral-clear: oklch(0.5638 0.0236 237.08);
			--me-neutral-dark: oklch(0.3714 0.0314 275);
			--me-font: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
			--me-text-xs: 0.75rem;
			--me-text-sm: 0.875rem;
			--me-radius-sm: 0.25rem;
			--me-radius-full: 9999px;
			--me-shadow-sm: 0 2px 5px #26303c33;
			--me-transition: 150ms cubic-bezier(0, 0, 0.2, 1);

			box-sizing: border-box;
			position: relative;
			display: flex;
			align-items: flex-start;
			gap: 1rem;
			width: 100%;
			max-width: 900px;
			padding: 1rem;
			background: var(--me-white);
			border-left: 4px solid var(--me-info);
			border-radius: 0 var(--me-radius-sm) var(--me-radius-sm) 0;
			box-shadow: var(--me-shadow-sm);
			font-family: var(--me-font);
			color: var(--me-neutral-dark);
			margin: 5px 0 2px;
		}
		.me-alert *, .me-alert *::before, .me-alert *::after { box-sizing: border-box; }
		.me-alert__body { flex: 1 1 auto; display: flex; flex-direction: column; gap: 0.75rem; min-width: 0; }
		.me-alert__title { margin: 0; font-size: var(--me-text-sm); font-weight: 700; line-height: 1.25; color: var(--me-neutral-dark); }
		.me-alert__description { margin: 0; font-size: var(--me-text-xs); font-weight: 400; line-height: 1.4; color: var(--me-neutral-clear); }
		.me-alert__chips { display: flex; flex-wrap: wrap; gap: 0.5rem; margin: 0; padding: 0; list-style: none; }
		.me-alert__chip { padding: 0.25rem 0.75rem; font-size: var(--me-text-xs); font-weight: 400; color: var(--me-neutral-dark); background: var(--me-neutral-bright); border: 1px solid var(--me-neutral-light); border-radius: var(--me-radius-full); white-space: nowrap; }
		.me-alert__actions { display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 0.25rem; }
		.me-alert__actions form { display: contents; }
		.me-alert__btn { display: inline-flex; align-items: center; justify-content: center; height: 3rem; padding: 0 1.5rem; font-family: inherit; font-size: var(--me-text-sm); font-weight: 700; line-height: 1.25; border: 1px solid transparent; border-radius: var(--me-radius-sm); box-shadow: var(--me-shadow-sm); cursor: pointer; text-decoration: none; transition: background-color var(--me-transition), color var(--me-transition), border-color var(--me-transition); }
		.me-alert__btn--primary { color: var(--me-white); background: var(--me-primary); }
		.me-alert__btn--primary:hover { background: var(--me-primary-light); }
		.me-alert__btn--primary:active { background: var(--me-primary-dark); }
		.me-alert__btn--secondary { color: var(--me-primary); background: var(--me-white); border-color: currentColor; }
		.me-alert__btn--secondary:hover { color: var(--me-primary-light); }
		.me-alert__btn--secondary:active { color: var(--me-primary-dark); }
		.me-alert__close-form { display: contents; }
		.me-alert__close { flex: 0 0 auto; display: inline-flex; align-items: center; justify-content: center; width: 1rem; height: 1rem; padding: 0; color: var(--me-neutral-clear); background: transparent; border: none; border-radius: var(--me-radius-full); cursor: pointer; transition: color var(--me-transition); box-shadow: none; }
		.me-alert__close:hover { color: var(--me-neutral-dark); }
		.me-alert__close svg { display: block; width: 100%; height: 100%; }
		@media (max-width: 480px) { .me-alert__actions { flex-direction: column; align-items: stretch; } }
		</style>
		<?php
	}

	private function renderUpgradeToIntegradorNotice(): void {
		$actionUrl = esc_url( admin_url( 'admin-post.php' ) );
		$chips     = array(
			__( 'Gestão centralizada', 'melhor-envio-cotacao' ),
			__( 'Etiquetas em lote', 'melhor-envio-cotacao' ),
			__( 'Regras de frete inteligentes', 'melhor-envio-cotacao' ),
		);
		?>
		<div class="notice me-alert" role="region" aria-labelledby="me-alert-title">
			<div class="me-alert__body">
				<h2 id="me-alert-title" class="me-alert__title">
					<?php esc_html_e( 'O plugin do Melhor Envio ficou ainda melhor', 'melhor-envio-cotacao' ); ?> 🚀
				</h2>
				<p class="me-alert__description">
					<?php esc_html_e( 'Melhorias para deixar sua operação mais rápida e fácil. A versão atual será descontinuada em 3 meses — e a migração leva poucos minutos.', 'melhor-envio-cotacao' ); ?>
				</p>
				<ul class="me-alert__chips">
					<?php foreach ( $chips as $chip ) : ?>
						<li class="me-alert__chip"><?php echo esc_html( $chip ); ?></li>
					<?php endforeach; ?>
				</ul>
				<div class="me-alert__actions">
					<form method="post" action="<?php echo $actionUrl; ?>">
						<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">
						<input type="hidden" name="mode" value="integrador">
						<?php wp_nonce_field( self::NONCE_KEY, '_wpnonce', false ); ?>
						<button type="submit" class="me-alert__btn me-alert__btn--primary">
							<?php esc_html_e( 'Migrar para nova versão', 'melhor-envio-cotacao' ); ?>
						</button>
					</form>
					<form method="post" action="<?php echo $actionUrl; ?>">
						<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">
						<input type="hidden" name="mode" value="dismiss">
						<?php wp_nonce_field( self::NONCE_KEY, '_wpnonce', false ); ?>
						<button type="submit" class="me-alert__btn me-alert__btn--secondary">
							<?php esc_html_e( 'Agora não', 'melhor-envio-cotacao' ); ?>
						</button>
					</form>
				</div>
			</div>

			<form class="me-alert__close-form" method="post" action="<?php echo $actionUrl; ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">
				<input type="hidden" name="mode" value="dismiss">
				<?php wp_nonce_field( self::NONCE_KEY, '_wpnonce', false ); ?>
				<button type="submit" class="me-alert__close" aria-label="<?php esc_attr_e( 'Fechar alerta', 'melhor-envio-cotacao' ); ?>">
					<svg viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
						<path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
					</svg>
				</button>
			</form>
		</div>
		<?php
	}

	private function renderReturnToLegacyNotice(): void {
		$screen = get_current_screen();
		if ( ! $screen || $screen->id !== 'woocommerce_page_melhor-integrador' ) {
			return;
		}

		$actionUrl = esc_url( admin_url( 'admin-post.php' ) );
		?>
		<div class="notice me-alert" role="region" aria-labelledby="me-alert-legacy-title">
			<div class="me-alert__body">
				<h2 id="me-alert-legacy-title" class="me-alert__title">
					<?php esc_html_e( 'Você está usando a nova versão do Melhor Envio', 'melhor-envio-cotacao' ); ?> 🚀
				</h2>
				<p class="me-alert__description">
					<?php esc_html_e( 'Se preferir, você pode voltar para a versão anterior do plugin a qualquer momento.', 'melhor-envio-cotacao' ); ?>
				</p>
				<div class="me-alert__actions">
					<form method="post" action="<?php echo $actionUrl; ?>">
						<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">
						<input type="hidden" name="mode" value="legacy">
						<?php wp_nonce_field( self::NONCE_KEY, '_wpnonce', false ); ?>
						<button type="submit" class="me-alert__btn me-alert__btn--secondary">
							<?php esc_html_e( 'Voltar para versão anterior', 'melhor-envio-cotacao' ); ?>
						</button>
					</form>
				</div>
			</div>

			<form class="me-alert__close-form" method="post" action="<?php echo $actionUrl; ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">
				<input type="hidden" name="mode" value="dismiss">
				<?php wp_nonce_field( self::NONCE_KEY, '_wpnonce', false ); ?>
				<button type="submit" class="me-alert__close" aria-label="<?php esc_attr_e( 'Fechar alerta', 'melhor-envio-cotacao' ); ?>">
					<svg viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
						<path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
					</svg>
				</button>
			</form>
		</div>
		<?php
	}

	public function handleModeSwitch(): void {
		if ( ! check_admin_referer( self::NONCE_KEY ) ) {
			wp_die( esc_html__( 'Ação não autorizada.', 'melhor-envio-cotacao' ) );
		}

		$mode = isset( $_POST['mode'] ) ? sanitize_text_field( wp_unslash( $_POST['mode'] ) ) : '';

		if ( $mode === 'dismiss' ) {
			update_user_meta( get_current_user_id(), self::DISMISS_META_KEY, time() );
			wp_safe_redirect( wp_get_referer() ?: admin_url() );
			exit;
		}

		if ( ! in_array( $mode, array( 'legacy', 'integrador' ), true ) ) {
			wp_die( esc_html__( 'Modo inválido.', 'melhor-envio-cotacao' ) );
		}

		PluginModeManager::setMode( $mode );

		if ( $mode === 'integrador' ) {
			wp_safe_redirect( admin_url( 'admin.php?page=melhor-integrador' ) );
		} else {
			wp_safe_redirect( admin_url( 'admin.php?page=melhor-envio' ) );
		}

		exit;
	}
}
