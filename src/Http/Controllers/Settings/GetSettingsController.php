<?php

declare(strict_types=1);

namespace MelhorEnvio\Http\Controllers\Settings;

use MelhorEnvio\Http\Controllers\Concerns\ValidatesAuthentication;
use MelhorEnvio\Http\Controllers\RestEndpointContract;
use MelhorEnvio\Services\Auth\SecretService;
use MelhorEnvio\Services\Settings\IntegradorSettingsService;
use WP_REST_Request;
use WP_REST_Response;

final class GetSettingsController extends RestEndpointContract {

	use ValidatesAuthentication;

	private IntegradorSettingsService $settingsService;
	private const ROUTE = '/settings';

	public function __construct(
		SecretService $secretManager,
		IntegradorSettingsService $settingsService
	) {
		$this->secretManager  = $secretManager;
		$this->settingsService = $settingsService;
	}

	public function registerRoute(): void {
		register_rest_route(
			self::API_NAMESPACE,
			self::ROUTE,
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'handleRequest' ),
				'permission_callback' => array( $this, 'checkSecretPermission' ),
			)
		);
	}

	public function handleRequest( WP_REST_Request $request ): WP_REST_Response {
		return new WP_REST_Response( $this->settingsService->getSettings(), 200 );
	}
}
