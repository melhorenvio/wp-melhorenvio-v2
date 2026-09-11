<?php

declare(strict_types=1);

namespace MelhorEnvio\Http\Controllers\Auth;

use MelhorEnvio\Services\Auth\SecretService;
use MelhorEnvio\Services\Auth\SignatureService;
use MelhorEnvio\Services\Shipping\ShippingZoneService;
use MelhorEnvio\Http\Controllers\Concerns\ValidatesAuthentication;
use MelhorEnvio\Http\Controllers\RestEndpointContract;
use WP_REST_Request;
use WP_REST_Response;

final class SaveSecretController extends RestEndpointContract {

	use ValidatesAuthentication;

	private ShippingZoneService $shippingZoneSetup;
	private const ROUTE = '/secret';

	public function __construct(
		SecretService $secretManager,
		SignatureService $signatureManager,
		ShippingZoneService $shippingZoneSetup
	) {
		$this->secretManager    = $secretManager;
		$this->signatureManager = $signatureManager;
		$this->shippingZoneSetup = $shippingZoneSetup;
	}

	public function registerRoute(): void {
		register_rest_route(
			self::API_NAMESPACE,
			self::ROUTE,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handleRequest' ),
				'permission_callback' => array( $this, 'checkSignaturePermission' ),
				'args'                => array(
					'secret' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	public function handleRequest( WP_REST_Request $request ): WP_REST_Response {
		$secret = $request->get_param( 'secret' );

		if ( empty( $secret ) ) {
			return new WP_REST_Response(
				array( 'message' => 'Secret is required.' ),
				400
			);
		}

		if ( ! $this->isValidSecretFormat( $secret ) ) {
			return new WP_REST_Response(
				array( 'message' => 'Invalid secret format.' ),
				400
			);
		}

		$result = $this->secretManager->setSecret( $secret );

		if ( ! $result ) {
			return new WP_REST_Response(
				array( 'message' => 'Failed to save secret.' ),
				500
			);
		}

		$this->shippingZoneSetup->ensureMethodRegistered();

		return new WP_REST_Response(
			array( 'message' => 'Secret saved successfully.' ),
			200
		);
	}

	private function isValidSecretFormat( string $secret ): bool {
		if ( substr( $secret, 0, 7 ) !== 'base64:' ) {
			return false;
		}

		$base64Part = substr( $secret, 7 );

		if ( empty( $base64Part ) ) {
			return false;
		}

		$decoded = base64_decode( $base64Part, true );
		return $decoded !== false;
	}
}
