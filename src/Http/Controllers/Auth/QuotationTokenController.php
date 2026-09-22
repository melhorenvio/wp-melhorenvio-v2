<?php

declare(strict_types=1);

namespace MelhorEnvio\Http\Controllers\Auth;

use MelhorEnvio\Services\Auth\SecretService;
use MelhorEnvio\Services\Shipping\ShippingZoneService;
use MelhorEnvio\Http\Controllers\Concerns\ValidatesAuthentication;
use MelhorEnvio\Http\Controllers\RestEndpointContract;
use WP_REST_Request;
use WP_REST_Response;

final class QuotationTokenController extends RestEndpointContract {

	use ValidatesAuthentication;

	private const ROUTE        = '/quotation-token';
	private const TOKEN_OPTION = 'melhor_envio_integrador_quotation_token';

	private ShippingZoneService $shippingZoneSetup;

	public function __construct(
		SecretService $secretManager,
		ShippingZoneService $shippingZoneSetup
	) {
		$this->secretManager    = $secretManager;
		$this->shippingZoneSetup = $shippingZoneSetup;
	}

	public function registerRoute(): void {
		register_rest_route(
			self::API_NAMESPACE,
			self::ROUTE,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handleRequest' ),
				'permission_callback' => array( $this, 'checkSecretPermission' ),
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
