<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Core;

use InvalidArgumentException;
use MelhorEnvio\Core\Container;
use MelhorEnvio\Tests\TestCase;
use MelhorEnvio\Tests\Unit\Core\Fixtures\AbstractGreeter;
use MelhorEnvio\Tests\Unit\Core\Fixtures\Branch;
use MelhorEnvio\Tests\Unit\Core\Fixtures\Greeter;
use MelhorEnvio\Tests\Unit\Core\Fixtures\GreeterInterface;
use MelhorEnvio\Tests\Unit\Core\Fixtures\Leaf;
use MelhorEnvio\Tests\Unit\Core\Fixtures\WithDefaultScalar;
use MelhorEnvio\Tests\Unit\Core\Fixtures\WithRequiredScalar;

require_once __DIR__ . '/Fixtures/ContainerFixtures.php';

final class ContainerTest extends TestCase {

	private Container $container;

	protected function setUp(): void {
		parent::setUp();
		$this->container = new Container();
	}

	public function test_autowires_constructor_dependencies(): void {
		$branch = $this->container->get( Branch::class );

		self::assertInstanceOf( Leaf::class, $branch->leaf );
		self::assertSame( $this->container, $branch->container );
	}

	public function test_bind_returns_new_instance_each_time(): void {
		$this->container->bind( GreeterInterface::class, Greeter::class );

		self::assertInstanceOf( Greeter::class, $this->container->get( GreeterInterface::class ) );
		self::assertNotSame(
			$this->container->get( GreeterInterface::class ),
			$this->container->get( GreeterInterface::class )
		);
	}

	public function test_singleton_returns_same_instance(): void {
		$this->container->singleton( GreeterInterface::class, Greeter::class );

		self::assertSame(
			$this->container->get( GreeterInterface::class ),
			$this->container->get( GreeterInterface::class )
		);
	}

	public function test_closure_binding_receives_container(): void {
		$received = null;
		$this->container->bind(
			Leaf::class,
			function ( Container $c ) use ( &$received ) {
				$received = $c;
				return new Leaf();
			}
		);

		$this->container->get( Leaf::class );

		self::assertSame( $this->container, $received );
	}

	public function test_uses_default_value_for_scalar_parameter(): void {
		self::assertSame( 3, $this->container->get( WithDefaultScalar::class )->retries );
	}

	public function test_throws_for_unbound_interface(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'is not bound' );

		$this->container->get( GreeterInterface::class );
	}

	public function test_throws_for_unbound_abstract_class(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Abstract class' );

		$this->container->get( AbstractGreeter::class );
	}

	public function test_throws_for_unresolvable_scalar_parameter(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Cannot resolve parameter apiKey' );

		$this->container->get( WithRequiredScalar::class );
	}

	public function test_throws_for_unknown_class(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'does not exist' );

		$this->container->get( 'MelhorEnvio\\DoesNotExist' );
	}
}
