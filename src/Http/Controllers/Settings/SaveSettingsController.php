<?php

declare(strict_types=1);

namespace MelhorEnvio\Http\Controllers\Settings;

use MelhorEnvio\Http\Controllers\Concerns\ValidatesAuthentication;
use MelhorEnvio\Http\Controllers\RestEndpointContract;
use MelhorEnvio\Services\Auth\SecretService;
use MelhorEnvio\Services\Settings\IntegradorSettingsService;
use WP_REST_Request;
use WP_REST_Response;

final class SaveSettingsController extends RestEndpointContract {

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
				'methods'             => 'POST',
				'callback'            => array( $this, 'handleRequest' ),
				'permission_callback' => array( $this, 'checkSecretPermission' ),
			)
		);
	}

	public function handleRequest( WP_REST_Request $request ): WP_REST_Response {
		$body = $request->get_json_params();

		if ( ! $this->isValidPayload( $body ) ) {
			return new WP_REST_Response( array( 'message' => 'Invalid payload.' ), 400 );
		}

		$settings = $this->sanitizeSettings( $body );
		$saved    = $this->settingsService->saveSettings( $settings );

		if ( ! $saved ) {
			return new WP_REST_Response( array( 'message' => 'Failed to save settings.' ), 500 );
		}

		return new WP_REST_Response( $settings, 200 );
	}

	private function isValidPayload( mixed $body ): bool {
		if ( ! is_array( $body ) ) {
			return false;
		}

		if ( isset( $body['calculator'] ) && ! is_array( $body['calculator'] ) ) {
			return false;
		}

		if ( isset( $body['dimensions_default'] ) && ! is_array( $body['dimensions_default'] ) ) {
			return false;
		}

		return true;
	}

	private function sanitizeSettings( array $body ): array {
		$settings = array();

		if ( isset( $body['calculator'] ) ) {
			$calc = $body['calculator'];
			$settings['calculator'] = array(
				'enabled'  => isset( $calc['enabled'] ) ? (bool) $calc['enabled'] : true,
				'position' => isset( $calc['position'] ) ? sanitize_text_field( $calc['position'] ) : 'woocommerce_before_add_to_cart_button',
			);
		}

		if ( isset( $body['dimensions_default'] ) ) {
			$dim = $body['dimensions_default'];
			$settings['dimensions_default'] = array(
				'height' => isset( $dim['height'] ) ? (float) $dim['height'] : 10,
				'width'  => isset( $dim['width'] ) ? (float) $dim['width'] : 10,
				'length' => isset( $dim['length'] ) ? (float) $dim['length'] : 10,
				'weight' => isset( $dim['weight'] ) ? (float) $dim['weight'] : 11,
			);
		}

		if ( isset( $body['checkout'] ) && is_array( $body['checkout'] ) ) {
			$checkout = $body['checkout'];
			$allowed_document_types = [ 'both', 'cpf_only', 'cnpj_only' ];
			$document_type = sanitize_text_field( $checkout['document_type'] ?? 'both' );

			$settings['checkout'] = array(
				'document_type'        => in_array( $document_type, $allowed_document_types, true ) ? $document_type : 'both',
				'require_number'       => isset( $checkout['require_number'] ) ? (bool) $checkout['require_number'] : true,
				'require_neighborhood' => isset( $checkout['require_neighborhood'] ) ? (bool) $checkout['require_neighborhood'] : true,
			);
		}

		return $settings;
	}
}
