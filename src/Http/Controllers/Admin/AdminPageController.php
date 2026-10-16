<?php

declare(strict_types=1);

namespace MelhorEnvio\Http\Controllers\Admin;

use MelhorEnvio\Services\Auth\SecretService;
use MelhorEnvio\Services\Auth\SignatureService;

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
		$secret    = $this->secretManager->getSecret();
		$hasSecret = $secret !== null;

		$secretJson = json_encode( $secret, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );

		$signature     = $this->signatureManager->getSignature();
		$signatureJson = json_encode( $signature, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );

		$baseUrl     = getenv( 'MELHOR_INTEGRADOR_BASE_URL' ) ?: get_option( 'melhor_integrador_base_url', 'https://woocommerceenvios.com' );
		$baseUrlJson = json_encode( $baseUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );
		?>
		<div class="wrap" id="melhor-envio-integrador-container" style="margin: 0; padding: 0; border: none;"></div>
		<script>
		(function() {
			var container = document.getElementById('melhor-envio-integrador-container');
			if (!container) return;

			var hasSecret = <?php echo $hasSecret ? 'true' : 'false'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
			var secret    = <?php echo $secretJson; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
			var signature = <?php echo $signatureJson; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
			var storeUrl  = window.location.origin;
			var baseUrl   = <?php echo $baseUrlJson; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;

			var iframeUrl = new URL(baseUrl);
			iframeUrl.pathname = iframeUrl.pathname.replace(/\/$/, '') + '/wp';

			if (hasSecret) {
				iframeUrl.searchParams.set('secret', secret);
			}

			iframeUrl.searchParams.set('url', storeUrl);
			iframeUrl.searchParams.set('signature', signature);

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
}
