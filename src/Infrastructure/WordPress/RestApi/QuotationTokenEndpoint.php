<?php

declare(strict_types=1);

namespace MelhorEnvio\Infrastructure\WordPress\RestApi;

use MelhorEnvio\Infrastructure\WordPress\Admin\SecretManager;
use MelhorEnvio\Infrastructure\WordPress\Shipping\ShippingZoneSetup;
use WP_REST_Request;
use WP_REST_Response;

final class QuotationTokenEndpoint {

	private const NAMESPACE   = 'wp-melhor-integrador/v1';
	private const ROUTE       = '/quotation-token';
	private const TOKEN_OPTION = 'melhor_envio_integrador_quotation_token';

	public function __construct(
		private readonly SecretManager $secretManager,
		private readonly ShippingZoneSetup $shippingZoneSetup,
	) {}

	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'registerRoute' ) );
	}

	public function registerRoute(): void {
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handleRequest' ),
				'permission_callback' => array( $this, 'checkPermission' ),
				'args'                => array(
					'quotation_token' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'timestamp'       => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'signature'       => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	public function checkPermission( WP_REST_Request $request ): bool {
		$signature = $request->get_param( 'signature' );
		$timestamp = $request->get_param( 'timestamp' );

		if ( empty( $signature ) || empty( $timestamp ) ) {
			return false;
		}

		return $this->validateHmac( $signature, $timestamp );
	}

	public function handleRequest( WP_REST_Request $request ): WP_REST_Response {
		$quotationToken = $request->get_param( 'quotation_token' );

		update_option( self::TOKEN_OPTION, $quotationToken );

		$this->shippingZoneSetup->ensureMethodRegistered();

		return new WP_REST_Response(
			array( 'message' => 'Quotation token saved successfully.' ),
			200
		);
	}

	/**
	 * Validates HMAC sent by backend: hash_hmac('sha256', $timestamp.'.quotation-token', $rawKey)
	 * where $rawKey = base64_decode($payload['secret']) extracted from the stored secret.
	 */
	private function validateHmac( string $signature, string $timestamp ): bool {
		$secret = $this->secretManager->getSecret();

		if ( empty( $secret ) || ! str_starts_with( $secret, 'base64:' ) ) {
			return false;
		}

		$outerDecoded = base64_decode( substr( $secret, 7 ), true );

		if ( $outerDecoded === false ) {
			return false;
		}

		$parts = explode( '.', $outerDecoded, 2 );

		if ( count( $parts ) !== 2 ) {
			return false;
		}

		$payload = json_decode( $parts[0], true );

		if ( ! is_array( $payload ) || ! isset( $payload['secret'] ) ) {
			return false;
		}

		$rawKey = base64_decode( $payload['secret'], true );

		if ( $rawKey === false ) {
			return false;
		}

		$expected = hash_hmac( 'sha256', $timestamp . '.quotation-token', $rawKey );

		return hash_equals( $expected, $signature );
	}
}
