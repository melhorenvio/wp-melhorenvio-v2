<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Core\Fixtures;

use MelhorEnvio\Core\Container;

interface GreeterInterface {}

final class Greeter implements GreeterInterface {}

abstract class AbstractGreeter {}

final class Leaf {}

final class Branch {

	public Leaf $leaf;

	public Container $container;

	public function __construct( Leaf $leaf, Container $container ) {
		$this->leaf      = $leaf;
		$this->container = $container;
	}
}

final class WithDefaultScalar {

	public int $retries;

	public function __construct( int $retries = 3 ) {
		$this->retries = $retries;
	}
}

final class WithRequiredScalar {

	public function __construct( string $apiKey ) {
	}
}
