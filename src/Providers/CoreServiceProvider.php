<?php

declare(strict_types=1);

namespace MelhorEnvio\Providers;

use MelhorEnvio\Core\Container;
use MelhorEnvio\Database\Contracts\DatabaseInterface;
use MelhorEnvio\Database\Repositories\WordPressDatabaseRepository;
use MelhorEnvio\Http\Controllers\Admin\AdminMenuController;
use MelhorEnvio\Services\Auth\SecretService;
use MelhorEnvio\Services\Auth\SignatureService;
use MelhorEnvio\Http\Controllers\Order\NFeXmlUploadController;
use MelhorEnvio\Http\Controllers\Quotation\QuotationController;
use MelhorEnvio\Http\Controllers\Frontend\ProductShippingCalculatorController;
use MelhorEnvio\Hooks\HookManager;
use MelhorEnvio\Services\Quotation\MelhorEnvioApiClientService;
use MelhorEnvio\Services\Quotation\PostalCodeLocationClientService;
use MelhorEnvio\Http\Controllers\Auth\DisconnectController;
use MelhorEnvio\Http\Controllers\Auth\QuotationTokenController;
use MelhorEnvio\Http\Controllers\Auth\SaveSecretController;
use MelhorEnvio\Services\Shipping\CartItemsBuilderService;
use MelhorEnvio\Services\Shipping\ShippingZoneService;
use wpdb;

final class CoreServiceProvider extends AbstractServiceProvider {

	public function register(): void {
		global $wpdb;

		$this->container->singleton( HookManager::class, HookManager::class );
		$this->container->singleton(
			AdminMenuController::class,
			static fn( Container $container ) => new AdminMenuController( $container )
		);
		$this->container->singleton( SecretService::class, SecretService::class );
		$this->container->singleton( SignatureService::class, SignatureService::class );
		$this->container->singleton(
			SaveSecretController::class,
			static fn( Container $container ) => new SaveSecretController(
				$container->get( SecretService::class ),
				$container->get( SignatureService::class ),
				$container->get( ShippingZoneService::class )
			)
		);
		$this->container->singleton(
			QuotationController::class,
			static fn( Container $container ) => new QuotationController(
				$container->get( MelhorEnvioApiClientService::class ),
				$container->get( CartItemsBuilderService::class ),
				$container->get( PostalCodeLocationClientService::class )
			)
		);
		$this->container->singleton( CartItemsBuilderService::class, CartItemsBuilderService::class );
		$this->container->singleton( ProductShippingCalculatorController::class, ProductShippingCalculatorController::class );
		$this->container->singleton( MelhorEnvioApiClientService::class, MelhorEnvioApiClientService::class );
		$this->container->singleton( PostalCodeLocationClientService::class, PostalCodeLocationClientService::class );
		$this->container->singleton( ShippingZoneService::class, ShippingZoneService::class );
		$this->container->singleton(
			QuotationTokenController::class,
			static fn( Container $container ) => new QuotationTokenController(
				$container->get( SecretService::class ),
				$container->get( ShippingZoneService::class )
			)
		);
		$this->container->singleton(
			DisconnectController::class,
			static fn( Container $container ) => new DisconnectController(
				$container->get( SecretService::class ),
				$container->get( ShippingZoneService::class )
			)
		);
		$this->container->singleton( NFeXmlUploadController::class, NFeXmlUploadController::class );
		$this->container->singleton(
			DatabaseInterface::class,
			static fn() => new WordPressDatabaseRepository( $wpdb )
		);
	}
}
