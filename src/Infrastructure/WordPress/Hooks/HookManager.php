<?php

declare(strict_types=1);

namespace MelhorEnvio\Infrastructure\WordPress\Hooks;

use MelhorEnvio\Core\Container;
use MelhorEnvio\Infrastructure\WordPress\Admin\AdminMenu;
use MelhorEnvio\Infrastructure\WordPress\Admin\SignatureManager;
use MelhorEnvio\Infrastructure\WordPress\Ajax\QuotationAjaxHandler;
use MelhorEnvio\Infrastructure\WordPress\Checkout\CheckoutFieldsManager;
use MelhorEnvio\Infrastructure\WordPress\Frontend\ProductShippingCalculator;
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

		$quotationAjaxHandler = $this->container->get( QuotationAjaxHandler::class );
		$quotationAjaxHandler->register();

		$productShippingCalculator = $this->container->get( ProductShippingCalculator::class );
		$productShippingCalculator->register();

		$checkoutFieldsManager = $this->container->get( CheckoutFieldsManager::class );
		$checkoutFieldsManager->register();

		$this->registerShippingMethod();
		$this->registerLogoutHook();
		$this->registerSslVerifyFilters();
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

	public function onUserLogout(): void {
		$signatureManager = $this->container->get( SignatureManager::class );
		$signatureManager->deleteSignature();
	}
}
