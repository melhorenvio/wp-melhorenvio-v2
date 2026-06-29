<?php

declare(strict_types=1);

namespace MelhorEnvio\Core\ServiceProvider;

use MelhorEnvio\Core\Container;
use MelhorEnvio\Core\ServiceProvider as ServiceProviderInterface;

abstract class AbstractServiceProvider implements ServiceProviderInterface {

	protected Container $container;

	public function __construct( Container $container ) {
		$this->container = $container;
	}
}
