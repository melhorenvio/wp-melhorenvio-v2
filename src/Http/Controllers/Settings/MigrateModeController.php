<?php

declare(strict_types=1);

namespace MelhorEnvio\Http\Controllers\Settings;

use MelhorEnvio\Http\Controllers\Concerns\ValidatesAuthentication;
use MelhorEnvio\Http\Controllers\RestEndpointContract;
use MelhorEnvio\Services\Admin\PluginModeService;
use MelhorEnvio\Services\Auth\SecretService;
use WP_REST_Response;

final class MigrateModeController extends RestEndpointContract {

	use ValidatesAuthentication;

	private const ROUTE = '/migrate';

	public function __construct( SecretService $secretManager ) {
		$this->secretManager = $secretManager;
	}

	public function registerRoute(): void {
		register_rest_route(
			self::API_NAMESPACE,
			self::ROUTE,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handleRequest' ),
				'permission_callback' => array( $this, 'checkSecretPermission' ),
			)
		);
	}

	public function handleRequest(): WP_REST_Response {
		if ( ! PluginModeService::isIntegradorMode() ) {
			PluginModeService::setMode( 'integrador' );
		}

		return new WP_REST_Response( array( 'mode' => 'integrador' ), 200 );
	}
}
