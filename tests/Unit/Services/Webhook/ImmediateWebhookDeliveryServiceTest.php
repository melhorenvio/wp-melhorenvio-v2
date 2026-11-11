<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Services\Webhook;

use MelhorEnvio\Services\Webhook\ImmediateWebhookDeliveryService;
use MelhorEnvio\Tests\TestCase;

final class ImmediateWebhookDeliveryServiceTest extends TestCase {

	public function test_order_is_not_marked_by_default(): void {
		self::assertFalse( ( new ImmediateWebhookDeliveryService() )->isOrderMarked( 10 ) );
	}

	public function test_marks_only_the_given_order(): void {
		$service = new ImmediateWebhookDeliveryService();

		$service->markOrder( 10 );

		self::assertTrue( $service->isOrderMarked( 10 ) );
		self::assertFalse( $service->isOrderMarked( 11 ) );
	}
}
