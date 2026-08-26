<?php

declare(strict_types=1);

namespace MelhorEnvio;

use MelhorEnvio\Core\Container;
use MelhorEnvio\Hooks\HookManager;
use MelhorEnvio\Providers\ApplicationServiceProvider;
use MelhorEnvio\Providers\CoreServiceProvider;

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
			CoreServiceProvider::class,
			ApplicationServiceProvider::class,
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
