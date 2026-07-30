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

	private SecretManager $secretManager;
	private ShippingZoneSetup $shippingZoneSetup;

	public function __construct(
		SecretManager $secretManager,
		ShippingZoneSetup $shippingZoneSetup
	) {
		$this->secretManager     = $secretManager;
		$this->shippingZoneSetup = $shippingZoneSetup;
	}

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
				),
			)
		);
	}

	public function checkPermission( WP_REST_Request $request ): bool {
		$secret = $request->get_header( 'X-ME-Secret' );

		if ( empty( $secret ) ) {
			return false;
		}

		$storedSecret = $this->secretManager->getSecret();

		return $storedSecret !== null && hash_equals( $storedSecret, $secret );
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
}
