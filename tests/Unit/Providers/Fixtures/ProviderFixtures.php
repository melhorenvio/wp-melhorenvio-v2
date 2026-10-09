<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Providers\Fixtures;

use MelhorEnvio\Core\Container;
use MelhorEnvio\Providers\AbstractServiceProvider;

final class FakeServiceProvider extends AbstractServiceProvider {

	public function register(): void {
		$this->container->bind( 'fake.service', static fn() => new \stdClass() );
	}

	public function container(): Container {
		return $this->container;
	}
}
