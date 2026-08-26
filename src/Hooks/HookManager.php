<?php

declare(strict_types=1);

namespace MelhorEnvio\Hooks;

use MelhorEnvio\Core\Container;
use MelhorEnvio\Http\Controllers\Admin\AdminMenuController;
use MelhorEnvio\Http\Controllers\Order\OrderInvoiceKeyMetaBoxController;
use MelhorEnvio\Services\Auth\SignatureService;
use MelhorEnvio\Http\Controllers\Order\NFeXmlUploadController;
use MelhorEnvio\Http\Controllers\Quotation\QuotationController;
use MelhorEnvio\Http\Controllers\Checkout\CheckoutFieldsController;
use MelhorEnvio\Http\Controllers\Frontend\ProductShippingCalculatorController;
use MelhorEnvio\Http\Controllers\Auth\DisconnectController;
use MelhorEnvio\Http\Controllers\Order\OrderMetaBackfillController;
use MelhorEnvio\Http\Controllers\Auth\QuotationTokenController;
use MelhorEnvio\Http\Controllers\Auth\SaveSecretController;
use MelhorEnvio\Services\Shipping\MelhorEnvioShippingService;

final class HookManager {

	private Container $container;

	public function __construct( Container $container ) {
		$this->container = $container;
	}

	public function register(): void {
		$adminMenu = $this->container->get( AdminMenuController::class );
		$adminMenu->register();

		$saveSecretEndpoint = $this->container->get( SaveSecretController::class );
		$saveSecretEndpoint->register();

		$quotationTokenEndpoint = $this->container->get( QuotationTokenController::class );
		$quotationTokenEndpoint->register();

		$disconnectEndpoint = $this->container->get( DisconnectController::class );
		$disconnectEndpoint->register();

		$quotationAjaxHandler = $this->container->get( QuotationController::class );
		$quotationAjaxHandler->register();

		$nfeXmlUploadHandler = $this->container->get( NFeXmlUploadController::class );
		$nfeXmlUploadHandler->register();

		$productShippingCalculator = $this->container->get( ProductShippingCalculatorController::class );
		$productShippingCalculator->register();

		$checkoutFieldsManager = $this->container->get( CheckoutFieldsController::class );
		$checkoutFieldsManager->register();

		$orderMetaBackfill = $this->container->get( OrderMetaBackfillController::class );
		$orderMetaBackfill->register();

		$orderInvoiceKeyMetaBox = $this->container->get( OrderInvoiceKeyMetaBoxController::class );
		$orderInvoiceKeyMetaBox->register();

		$this->registerShippingMethod();
		$this->registerLogoutHook();
		$this->registerSslVerifyFilters();
		$this->registerWcAuthApproveGuard();
		$this->registerHiddenOrderItemMeta();
	}

	private function registerHiddenOrderItemMeta(): void {
		add_filter(
			'woocommerce_hidden_order_itemmeta',
			static function ( array $hidden ): array {
				return array_merge(
					$hidden,
					array(
						'me_service_id',
						'me_service_name',
						'me_company_id',
						'me_company_name',
						'me_delivery_time',
					)
				);
			}
		);
	}

	private function registerShippingMethod(): void {
		add_filter(
			'woocommerce_shipping_methods',
			static function ( array $methods ): array {
				$methods['melhor_envio'] = MelhorEnvioShippingService::class;
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
		$signatureManager = $this->container->get( SignatureService::class );
		$signatureManager->deleteSignature();
	}
}
