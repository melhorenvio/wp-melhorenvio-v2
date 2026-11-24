<?php

declare(strict_types=1);

namespace MelhorEnvio\Services\Webhook;

final class ImmediateWebhookDeliveryService {

	private array $orderIds = array();

	public function markOrder( int $orderId ): void {
		$this->orderIds[ $orderId ] = true;
	}

	public function isOrderMarked( int $orderId ): bool {
		return isset( $this->orderIds[ $orderId ] );
	}

	public function hasMarkedOrders(): bool {
		return array() !== $this->orderIds;
	}
}
