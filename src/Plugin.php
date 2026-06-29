<?php

declare(strict_types=1);

namespace MelhorEnvio;

use MelhorEnvio\Core\Container;
use MelhorEnvio\Core\ServiceProvider;
use MelhorEnvio\Infrastructure\WordPress\Hooks\HookManager;

final class Plugin {

	private Container $container;
	private HookManager $hookManager;

	public function boot(): void {
		$this->container = new Container();

		$this->registerServiceProviders();

		$this->hookManager = $this->container->get( HookManager::class );
		$this->hookManager->register();
	}

	private function registerServiceProviders(): void {
		$providers = array(
			ServiceProvider\CoreServiceProvider::class,
			ServiceProvider\ApplicationServiceProvider::class,
		);

		foreach ( $providers as $providerClass ) {
			$provider = new $providerClass( $this->container );
			$provider->register();
		}
	}

	public function getContainer(): Container {
		return $this->container;
	}
}
