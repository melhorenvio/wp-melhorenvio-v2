<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Providers;

use MelhorEnvio\Core\Container;
use MelhorEnvio\Core\Contracts\ServiceProviderInterface;
use MelhorEnvio\Providers\ApplicationServiceProvider;
use MelhorEnvio\Tests\TestCase;
use ReflectionProperty;

final class ApplicationServiceProviderTest extends TestCase {

	public function test_is_a_service_provider(): void {
		self::assertInstanceOf( ServiceProviderInterface::class, new ApplicationServiceProvider( new Container() ) );
	}

	public function test_register_adds_no_bindings(): void {
		$container = new Container();

		( new ApplicationServiceProvider( $container ) )->register();

		$bindings = new ReflectionProperty( Container::class, 'bindings' );
		$bindings->setAccessible( true );

		self::assertSame( array(), $bindings->getValue( $container ) );
	}
}
