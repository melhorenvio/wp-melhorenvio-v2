<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Providers;

use MelhorEnvio\Core\Container;
use MelhorEnvio\Core\Contracts\ServiceProviderInterface;
use MelhorEnvio\Tests\TestCase;
use MelhorEnvio\Tests\Unit\Providers\Fixtures\FakeServiceProvider;

require_once __DIR__ . '/Fixtures/ProviderFixtures.php';

final class AbstractServiceProviderTest extends TestCase {

	public function test_is_a_service_provider(): void {
		self::assertInstanceOf( ServiceProviderInterface::class, new FakeServiceProvider( new Container() ) );
	}

	public function test_exposes_injected_container_to_subclasses(): void {
		$container = new Container();

		self::assertSame( $container, ( new FakeServiceProvider( $container ) )->container() );
	}

	public function test_subclass_registers_bindings_into_injected_container(): void {
		$container = new Container();

		( new FakeServiceProvider( $container ) )->register();

		self::assertInstanceOf( \stdClass::class, $container->get( 'fake.service' ) );
	}
}
