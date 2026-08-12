<?php

declare(strict_types=1);

namespace MelhorEnvio\Infrastructure\WordPress\RestApi;

use MelhorEnvio\Infrastructure\WordPress\Admin\SecretManager;
use MelhorEnvio\Infrastructure\WordPress\Shipping\ShippingZoneSetup;
use WP_REST_Request;
use WP_REST_Response;

final class DisconnectEndpoint {

	private const NAMESPACE = 'wp-melhor-integrador/v1';
	private const ROUTE     = '/disconnect';

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
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'handleRequest' ),
				'permission_callback' => array( $this, 'checkPermission' ),
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
