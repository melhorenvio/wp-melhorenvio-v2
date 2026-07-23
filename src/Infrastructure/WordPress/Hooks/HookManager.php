<?php

declare(strict_types=1);

namespace MelhorEnvio\Infrastructure\WordPress\Hooks;

use MelhorEnvio\Core\Container;
use MelhorEnvio\Infrastructure\WordPress\Admin\AdminMenu;
use MelhorEnvio\Infrastructure\WordPress\Admin\SignatureManager;
use MelhorEnvio\Infrastructure\WordPress\Ajax\QuotationAjaxHandler;
use MelhorEnvio\Infrastructure\WordPress\Checkout\CheckoutFieldsManager;
use MelhorEnvio\Infrastructure\WordPress\Checkout\CheckoutOrderHandler;
use MelhorEnvio\Infrastructure\WordPress\Frontend\ProductShippingCalculator;
use MelhorEnvio\Infrastructure\WordPress\RestApi\DisconnectEndpoint;
use MelhorEnvio\Infrastructure\WordPress\RestApi\QuotationTokenEndpoint;
use MelhorEnvio\Infrastructure\WordPress\RestApi\SaveSecretEndpoint;
use MelhorEnvio\Infrastructure\WordPress\Shipping\MelhorEnvioShippingMethod;

final class HookManager {

	private Container $container;

	public function __construct( Container $container ) {
		$this->container = $container;
	}

	public function register(): void {
		$adminMenu = $this->container->get( AdminMenu::class );
		$adminMenu->register();

		$saveSecretEndpoint = $this->container->get( SaveSecretEndpoint::class );
		$saveSecretEndpoint->register();

		$quotationTokenEndpoint = $this->container->get( QuotationTokenEndpoint::class );
		$quotationTokenEndpoint->register();

		$disconnectEndpoint = $this->container->get( DisconnectEndpoint::class );
		$disconnectEndpoint->register();

		$quotationAjaxHandler = $this->container->get( QuotationAjaxHandler::class );
		$quotationAjaxHandler->register();

		$productShippingCalculator = $this->container->get( ProductShippingCalculator::class );
		$productShippingCalculator->register();

		$checkoutFieldsManager = $this->container->get( CheckoutFieldsManager::class );
		$checkoutFieldsManager->register();

		$checkoutOrderHandler = $this->container->get( CheckoutOrderHandler::class );
		$checkoutOrderHandler->register();

		$this->registerShippingMethod();
		$this->registerLogoutHook();
		$this->registerSslVerifyFilters();
		$this->registerWcAuthApproveGuard();
	}

	private function registerShippingMethod(): void {
		add_filter(
			'woocommerce_shipping_methods',
			static function ( array $methods ): array {
				$methods['melhor_envio'] = MelhorEnvioShippingMethod::class;
				return $methods;
			}
		);
	}

	private function registerLogoutHook(): void {
		add_action( 'wp_logout', array( $this, 'onUserLogout' ) );
	}

	private function registerSslVerifyFilters(): void {
		add_filter( 'https_ssl_verify', '__return_false' );
		add_filter( 'https_local_ssl_verify', '__return_false' );
	}

	private function registerWcAuthApproveGuard(): void {
		add_action(
			'woocommerce_auth_page_footer',
			static function (): void {
				?>
				<script>
				(function () {
					var btn = document.querySelector('.wc-auth-approve');
					if (!btn) return;
					btn.addEventListener('click', function (e) {
						if (btn.dataset.clicked) {
							e.preventDefault();
							return;
						}
						btn.dataset.clicked = '1';
						btn.style.opacity = '0.6';
						btn.style.pointerEvents = 'none';
						btn.textContent = btn.textContent + '...';
					});
				})();
				</script>
				<?php
			}
		);
	}

	public function onUserLogout(): void {
		$signatureManager = $this->container->get( SignatureManager::class );
		$signatureManager->deleteSignature();
	}
}
