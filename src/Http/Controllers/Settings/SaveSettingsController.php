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
			$calculator = array();

			if ( isset( $calc['enabled'] ) ) {
				$calculator['enabled'] = (bool) $calc['enabled'];
			}

			if ( isset( $calc['position'] ) ) {
				$calculator['position'] = sanitize_text_field( $calc['position'] );
			}

			$settings['calculator'] = $calculator;
		}

		if ( isset( $body['dimensions_default'] ) ) {
			$dim = $body['dimensions_default'];
			$dimensions = array();

			if ( isset( $dim['height'] ) ) {
				$dimensions['height'] = (float) $dim['height'];
			}

			if ( isset( $dim['width'] ) ) {
				$dimensions['width'] = (float) $dim['width'];
			}

			if ( isset( $dim['length'] ) ) {
				$dimensions['length'] = (float) $dim['length'];
			}

			if ( isset( $dim['weight'] ) ) {
				$dimensions['weight'] = (float) $dim['weight'];
			}

			$settings['dimensions_default'] = $dimensions;
		}

		if ( isset( $body['checkout'] ) && is_array( $body['checkout'] ) ) {
			$checkout = $body['checkout'];
			$allowed_document_types = [ 'both', 'cpf_only', 'cnpj_only' ];
			$checkout_settings = array();

			if ( isset( $checkout['document_type'] ) ) {
				$document_type = sanitize_text_field( $checkout['document_type'] );
				if ( in_array( $document_type, $allowed_document_types, true ) ) {
					$checkout_settings['document_type'] = $document_type;
				}
			}

			if ( isset( $checkout['require_number'] ) ) {
				$checkout_settings['require_number'] = (bool) $checkout['require_number'];
			}

			if ( isset( $checkout['require_neighborhood'] ) ) {
				$checkout_settings['require_neighborhood'] = (bool) $checkout['require_neighborhood'];
			}

			$settings['checkout'] = $checkout_settings;
		}

		return $settings;
	}
}
