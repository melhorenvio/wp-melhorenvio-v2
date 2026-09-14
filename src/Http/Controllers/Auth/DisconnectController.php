<?php

declare(strict_types=1);

namespace MelhorEnvio\Http\Controllers\Auth;

use MelhorEnvio\Services\Auth\SecretService;
use MelhorEnvio\Services\Shipping\ShippingZoneService;
use MelhorEnvio\Http\Controllers\Concerns\ValidatesAuthentication;
use MelhorEnvio\Http\Controllers\RestEndpointContract;
use WP_REST_Request;
use WP_REST_Response;

final class DisconnectController extends RestEndpointContract {

	use ValidatesAuthentication;

	private const ROUTE = '/disconnect';

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
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'handleRequest' ),
				'permission_callback' => array( $this, 'checkSecretPermission' ),
			)
		);
	}

	public function handleRequest( WP_REST_Request $request ): WP_REST_Response {
		$this->secretManager->deleteSecret();
		delete_option( 'melhor_envio_integrador_quotation_token' );
		$this->shippingZoneSetup->removeMethod();
		$this->deleteMelhorEnvioWebhooks();

		return new WP_REST_Response(
			array( 'message' => 'Disconnected successfully.' ),
			200
		);
	}

	private function deleteMelhorEnvioWebhooks(): void {
		$data_store = \WC_Data_Store::load( 'webhook' );
		$ids        = $data_store->get_webhooks_ids();

		foreach ( $ids as $id ) {
			$webhook      = new \WC_Webhook( $id );
			$delivery_url = $webhook->get_delivery_url();

			if ( str_contains( $delivery_url, 'webhook-wordpress-envios.melhorenvio' ) ||
				str_contains( $delivery_url, 'webhook.woocommerceenvios' ) ) {
				$webhook->delete( true );
			}
		}
	}
}
