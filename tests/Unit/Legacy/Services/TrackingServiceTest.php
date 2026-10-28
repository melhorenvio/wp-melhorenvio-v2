<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Legacy\Services;

use MelhorEnvio\Services\TrackingService;
use MelhorEnvio\Tests\TestCase;
use Mockery;

final class TrackingServiceTest extends TestCase {

	private const META_KEY = 'melhorenvio_tracking';

	public function test_does_not_save_order_when_tracking_is_unchanged(): void {
		$order = Mockery::mock( 'WC_Order' );
		$order->allows( 'get_meta' )->with( self::META_KEY )->andReturn( 'ME123BR' );
		$order->expects( 'update_meta_data' )->never();
		$order->expects( 'save' )->never();

		( new TrackingService() )->addTrackingWcOrder( $order, 'ME123BR' );

		$this->assertMockExpectationsMet();
	}

	public function test_saves_order_when_tracking_changes(): void {
		$order = Mockery::mock( 'WC_Order' );
		$order->allows( 'get_meta' )->with( self::META_KEY )->andReturn( '' );
		$order->expects( 'update_meta_data' )->with( self::META_KEY, 'ME123BR' );
		$order->expects( 'save' );

		( new TrackingService() )->addTrackingWcOrder( $order, 'ME123BR' );

		$this->assertMockExpectationsMet();
	}
}
