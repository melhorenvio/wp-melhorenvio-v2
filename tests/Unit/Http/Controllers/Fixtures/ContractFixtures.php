<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Http\Controllers\Fixtures;

use MelhorEnvio\Http\Controllers\AjaxHandlerContract;
use MelhorEnvio\Http\Controllers\RestEndpointContract;

// Named (not anonymous) classes: Brain Monkey can't stringify callbacks bound to anonymous classes.

final class FakeRestEndpoint extends RestEndpointContract {

	/** @var int */
	public $routesRegistered = 0;

	public function registerRoute(): void {
		++$this->routesRegistered;
	}

	public function apiNamespace(): string {
		return self::API_NAMESPACE;
	}
}

final class FakeAjaxHandler extends AjaxHandlerContract {

	/** @var int */
	public $processed = 0;

	protected function process(): void {
		++$this->processed;
	}

	/**
	 * @param mixed $default
	 * @return mixed
	 */
	public function input( string $key, $default = null ) {
		return func_num_args() > 1 ? $this->getInput( $key, $default ) : $this->getInput( $key );
	}

	/** @param mixed ...$args */
	public function success( ...$args ): void {
		$this->sendSuccess( ...$args );
	}

	/** @param mixed ...$args */
	public function error( ...$args ): void {
		$this->sendError( ...$args );
	}
}
