<?php

declare(strict_types=1);

namespace MelhorEnvio\Http\Controllers;

use MelhorEnvio\Http\Controllers\Contracts\ControllerInterface;

abstract class RestEndpointContract implements ControllerInterface {

	protected const API_NAMESPACE = 'wp-melhor-integrador/v1';

	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'registerRoute' ) );
	}

	abstract public function registerRoute(): void;
}
