<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Services\Webhook;

use MelhorEnvio\Services\Webhook\MelhorIntegradorWebhookService;
use MelhorEnvio\Tests\TestCase;
use Mockery;
use WC_Data_Store;
use WC_Webhook;

final class MelhorIntegradorWebhookServiceTest extends TestCase {

	private const MI_URL    = 'https://webhook-wordpress-envios.melhorenvio.com.br/hook';
	private const OTHER_URL = 'https://example.com/hook';

	private MelhorIntegradorWebhookService $service;

	protected function setUp(): void {
		parent::setUp();
		$this->service = new MelhorIntegradorWebhookService();
	}

	/**
	 * @dataProvider deliveryUrls
	 */
	public function test_is_melhor_integrador_url( string $url, bool $expected ): void {
		self::assertSame( $expected, $this->service->isMelhorIntegradorUrl( $url ) );
	}

	public function deliveryUrls(): array {
		return array(
			'melhorenvio host'       => array( self::MI_URL, true ),
			'woocommerceenvios host' => array( 'https://webhook.woocommerceenvios.com/hook', true ),
			'third party'            => array( self::OTHER_URL, false ),
			'similar path only'      => array( 'https://example.com/melhorenvio', false ),
			'empty'                  => array( '', false ),
		);
	}

	public function test_has_any_considers_webhooks_in_any_status(): void {
		$this->givenWebhooks(
			array(
				1 => array( 'delivery_url' => self::OTHER_URL, 'status' => 'active' ),
				2 => array( 'delivery_url' => self::MI_URL, 'status' => 'disabled' ),
			)
		);

		self::assertTrue( $this->service->hasAny() );
	}

	public function test_has_any_is_false_without_melhor_integrador_webhook(): void {
		$this->givenWebhooks( array( 1 => array( 'delivery_url' => self::OTHER_URL, 'status' => 'active' ) ) );

		self::assertFalse( $this->service->hasAny() );
	}

	public function test_deliverable_webhooks_prefer_order_updated(): void {
		$this->givenWebhooks(
			array(
				1 => array( 'delivery_url' => self::MI_URL, 'status' => 'active', 'topic' => 'order.created' ),
				2 => array( 'delivery_url' => self::MI_URL, 'status' => 'active', 'topic' => 'order.updated' ),
				3 => array( 'delivery_url' => self::OTHER_URL, 'status' => 'active', 'topic' => 'order.updated' ),
				4 => array( 'delivery_url' => self::MI_URL, 'status' => 'active', 'topic' => 'order.deleted' ),
				5 => array( 'delivery_url' => self::MI_URL, 'status' => 'paused', 'topic' => 'order.updated' ),
			)
		);

		self::assertSame( array( 2 ), $this->ids( $this->service->getDeliverableWebhooks() ) );
	}

	public function test_deliverable_webhooks_fall_back_to_order_created(): void {
		$this->givenWebhooks(
			array(
				1 => array( 'delivery_url' => self::MI_URL, 'status' => 'active', 'topic' => 'order.created' ),
				2 => array( 'delivery_url' => self::OTHER_URL, 'status' => 'active', 'topic' => 'order.updated' ),
				3 => array( 'delivery_url' => self::MI_URL, 'status' => 'disabled', 'topic' => 'order.updated' ),
			)
		);

		self::assertSame( array( 1 ), $this->ids( $this->service->getDeliverableWebhooks() ) );
	}

	public function test_deliverable_webhooks_empty_without_active_melhor_integrador_webhook(): void {
		$this->givenWebhooks(
			array(
				1 => array( 'delivery_url' => self::MI_URL, 'status' => 'disabled', 'topic' => 'order.updated' ),
				2 => array( 'delivery_url' => self::OTHER_URL, 'status' => 'active', 'topic' => 'order.updated' ),
			)
		);

		self::assertSame( array(), $this->service->getDeliverableWebhooks() );
	}

	/**
	 * Seeds the fake webhooks and a data store that filters ids by status like WooCommerce does.
	 *
	 * @param array<int, array<string, string>> $records
	 */
	private function givenWebhooks( array $records ): void {
		WC_Webhook::$records = $records;

		$store = Mockery::mock();
		$store->allows( 'get_webhooks_ids' )->andReturnUsing(
			static function ( $status = '' ) use ( $records ): array {
				return array_keys(
					array_filter(
						$records,
						static fn( array $record ): bool => $status === '' || $record['status'] === $status
					)
				);
			}
		);
		WC_Data_Store::$stores['webhook'] = $store;
	}

	/**
	 * @param WC_Webhook[] $webhooks
	 * @return int[]
	 */
	private function ids( array $webhooks ): array {
		return array_map( static fn( WC_Webhook $webhook ): int => $webhook->get_id(), $webhooks );
	}
}
