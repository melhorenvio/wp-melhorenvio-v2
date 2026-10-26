<?php

declare(strict_types=1);

namespace MelhorEnvio\Http\Controllers\Admin;

use MelhorEnvio\Services\Auth\SecretService;
use MelhorEnvio\Services\Auth\SignatureService;
use MelhorEnvio\Support\Capabilities;

final class AdminPageController {

	private string $menuSlug;
	private SecretService $secretManager;
	private SignatureService $signatureManager;

	public function __construct(
		string $menuSlug,
		SecretService $secretManager,
		SignatureService $signatureManager
	) {
		$this->menuSlug         = $menuSlug;
		$this->secretManager    = $secretManager;
		$this->signatureManager = $signatureManager;
	}

	public function render(): void {
		$secret           = $this->secretManager->getSecret();
		$hasSecret        = $secret !== null;
		$canManageAccount = Capabilities::canManageAccount();

		// Conectar a conta exige a assinatura; sem conta conectada, só o administrador segue.
		if ( ! $hasSecret && ! $canManageAccount ) {
			$this->renderAccountRestrictedNotice();
			return;
		}

		$secretJson = json_encode( $secret, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );

		// A assinatura autoriza salvar um novo secret (reconectar/trocar a conta): só vai para o administrador.
		$signature     = $canManageAccount ? $this->signatureManager->getSignature() : null;
		$signatureJson = json_encode( $signature, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );

		$baseUrl     = getenv( 'MELHOR_INTEGRADOR_BASE_URL' ) ?: get_option( 'melhor_integrador_base_url', 'https://woocommerceenvios.com' );
		$baseUrlJson = json_encode( $baseUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );

		// Caminho da loja (ex.: /loja em https://exemplo.com.br/loja), somado no JS à origem do navegador.
		// A origem continua vindo do navegador para não mudar esquema/host das lojas já conectadas (é a
		// identity delas no backend); só o subdiretório, que window.location.origin perde, vem da home.
		// Usa a opção crua 'home' porque o filtro 'home_url' recebe o prefixo de idioma do WPML/Polylang.
		$storePath     = untrailingslashit( (string) wp_parse_url( (string) get_option( 'home' ), PHP_URL_PATH ) );
		$storePathJson = wp_json_encode( $storePath, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );
		?>
		<div class="wrap" id="melhor-envio-integrador-container" style="margin: 0; padding: 0; border: none;"></div>
		<script>
		(function() {
			var container = document.getElementById('melhor-envio-integrador-container');
			if (!container) return;

			var hasSecret = <?php echo $hasSecret ? 'true' : 'false'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
			var secret    = <?php echo $secretJson; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
			var signature = <?php echo $signatureJson; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
			var storeUrl  = window.location.origin + <?php echo $storePathJson; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
			var baseUrl   = <?php echo $baseUrlJson; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;

			var iframeUrl = new URL(baseUrl);
			iframeUrl.pathname = iframeUrl.pathname.replace(/\/$/, '') + '/wp';

			if (hasSecret) {
				iframeUrl.searchParams.set('secret', secret);
			}

			iframeUrl.searchParams.set('url', storeUrl);

			if (signature) {
				iframeUrl.searchParams.set('signature', signature);
			}

			var iframe = document.createElement('iframe');

			iframe.id            = 'melhor-envio-integrador-iframe';
			iframe.src           = iframeUrl.toString();
			iframe.style.width   = '100%';
			iframe.style.height  = 'calc(100vh - 32px)';
			iframe.style.border  = 'none';
			iframe.style.display = 'block';

			container.appendChild(iframe);

			window.addEventListener('message', function(event) {
				if (event.data && event.data.state === 'READY') {
					iframe.contentWindow.postMessage(
						{ signature: signature, url: storeUrl },
						event.origin
					);
				}
			});
		})();
		</script>
		<?php
	}

	private function renderAccountRestrictedNotice(): void {
		?>
		<div class="wrap">
			<div class="notice notice-warning inline">
				<p><?php esc_html_e( 'A conta Melhor Envio ainda não está conectada. Peça a um administrador da loja para conectá-la.', 'melhor-envio-cotacao' ); ?></p>
			</div>
		</div>
		<?php
	}
}
