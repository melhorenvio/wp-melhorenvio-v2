<?php

declare(strict_types=1);

namespace MelhorEnvio\Core\ServiceProvider;

use MelhorEnvio\Core\Container;
use MelhorEnvio\Infrastructure\Database\DatabaseInterface;
use MelhorEnvio\Infrastructure\Database\WordPressDatabase;
use MelhorEnvio\Infrastructure\WordPress\Admin\AdminMenu;
use MelhorEnvio\Infrastructure\WordPress\Admin\SecretManager;
use MelhorEnvio\Infrastructure\WordPress\Admin\SignatureManager;
use MelhorEnvio\Infrastructure\WordPress\Ajax\QuotationAjaxHandler;
use MelhorEnvio\Infrastructure\WordPress\Frontend\ProductShippingCalculator;
use MelhorEnvio\Infrastructure\WordPress\Hooks\HookManager;
use MelhorEnvio\Infrastructure\WordPress\Http\MelhorEnvioApiClient;
use MelhorEnvio\Infrastructure\WordPress\RestApi\DisconnectEndpoint;
use MelhorEnvio\Infrastructure\WordPress\RestApi\QuotationTokenEndpoint;
use MelhorEnvio\Infrastructure\WordPress\RestApi\SaveSecretEndpoint;
use MelhorEnvio\Infrastructure\WordPress\Shipping\ShippingZoneSetup;
use wpdb;

final class CoreServiceProvider extends AbstractServiceProvider {

	public function register(): void {
		global $wpdb;

		$this->container->singleton( HookManager::class, HookManager::class );
		$this->container->singleton(
			AdminMenu::class,
			static fn( Container $container ) => new AdminMenu( $container )
		);
		$this->container->singleton( SecretManager::class, SecretManager::class );
		$this->container->singleton( SignatureManager::class, SignatureManager::class );
		$this->container->singleton(
			SaveSecretEndpoint::class,
			static fn( Container $container ) => new SaveSecretEndpoint(
				$container->get( SecretManager::class ),
				$container->get( SignatureManager::class ),
				$container->get( ShippingZoneSetup::class )
			)
		);
		$this->container->singleton(
			QuotationAjaxHandler::class,
			static fn( Container $container ) => new QuotationAjaxHandler(
				$container->get( MelhorEnvioApiClient::class )
			)
		);
		$this->container->singleton( ProductShippingCalculator::class, ProductShippingCalculator::class );
		$this->container->singleton( MelhorEnvioApiClient::class, MelhorEnvioApiClient::class );
		$this->container->singleton( ShippingZoneSetup::class, ShippingZoneSetup::class );
		$this->container->singleton(
			QuotationTokenEndpoint::class,
			static fn( Container $container ) => new QuotationTokenEndpoint(
				$container->get( SecretManager::class ),
				$container->get( ShippingZoneSetup::class )
			)
		);
		$this->container->singleton(
			DisconnectEndpoint::class,
			static fn( Container $container ) => new DisconnectEndpoint(
				$container->get( SecretManager::class ),
				$container->get( ShippingZoneSetup::class )
			)
		);
		$this->container->singleton(
			DatabaseInterface::class,
			static fn() => new WordPressDatabase( $wpdb )
		);
	}
}
