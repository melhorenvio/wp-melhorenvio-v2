<?php

declare(strict_types=1);

namespace MelhorEnvio\Services\Webhook;

/**
 * Localiza os webhooks do WooCommerce criados pelo Melhor Integrador
 * (identificados pela URL de entrega).
 */
final class MelhorIntegradorWebhookService {

	private const DELIVERY_URL_MARKERS = array(
		'webhook-wordpress-envios.melhorenvio',
		'webhook.woocommerceenvios',
	);

	private const PREFERRED_TOPIC = 'order.updated';
	private const FALLBACK_TOPIC  = 'order.created';

	public function isMelhorIntegradorUrl( string $url ): bool {
		foreach ( self::DELIVERY_URL_MARKERS as $marker ) {
			if ( str_contains( $url, $marker ) ) {
				return true;
			}
		}

		return false;
	}

	public function isMelhorIntegradorWebhook( \WC_Webhook $webhook ): bool {
		return $this->isMelhorIntegradorUrl( (string) $webhook->get_delivery_url() );
	}

	/**
	 * Qualquer webhook do MI, independente do status.
	 */
	public function hasAny(): bool {
		foreach ( $this->getWebhooks() as $webhook ) {
			if ( $this->isMelhorIntegradorWebhook( $webhook ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Webhooks ativos do MI que devem receber a importação forçada: os de order.updated
	 * ou, se não houver nenhum, os de order.created.
	 *
	 * @return \WC_Webhook[]
	 */
	public function getDeliverableWebhooks(): array {
		$byTopic = array(
			self::PREFERRED_TOPIC => array(),
			self::FALLBACK_TOPIC  => array(),
		);

		foreach ( $this->getWebhooks( 'active' ) as $webhook ) {
			$topic = $webhook->get_topic();

			if ( isset( $byTopic[ $topic ] ) && $this->isMelhorIntegradorWebhook( $webhook ) ) {
				$byTopic[ $topic ][] = $webhook;
			}
		}

		return $byTopic[ self::PREFERRED_TOPIC ] ?: $byTopic[ self::FALLBACK_TOPIC ];
	}

	/**
	 * @return \WC_Webhook[]
	 */
	public function getWebhooks( string $status = '' ): array {
		$ids = \WC_Data_Store::load( 'webhook' )->get_webhooks_ids( $status );

		return array_map(
			static fn( $id ): \WC_Webhook => new \WC_Webhook( $id ),
			$ids
		);
	}
}
