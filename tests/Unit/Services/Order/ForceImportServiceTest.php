<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Services\Order;

use MelhorEnvio\Services\Order\ForceImportService;
use MelhorEnvio\Tests\TestCase;
use Mockery;

final class ForceImportServiceTest extends TestCase {

	private const META_KEY = '_melhor_integrador_force_import';

	private ForceImportService $service;

	protected function setUp(): void {
		parent::setUp();
		$this->service = new ForceImportService();
	}

	public function test_keys_match_melhor_integrador_contract(): void {
		self::assertSame( self::META_KEY, ForceImportService::META_KEY );
		self::assertSame( 'melhor_integrador_force_import', ForceImportService::PAYLOAD_KEY );
		self::assertSame( 3600, ForceImportService::TTL );
	}

	public function test_mark_stores_expiration_one_hour_ahead_without_full_save(): void {
		$stored = null;
		$order  = Mockery::mock( 'WC_Order' );
		$order->expects( 'update_meta_data' )->with( self::META_KEY, Mockery::type( 'int' ) )->andReturnUsing(
			static function ( $key, $value ) use ( &$stored ): void {
				$stored = $value;
			}
		);
		$order->expects( 'save_meta_data' );
		$order->expects( 'save' )->never();

		$before = time();
		$this->service->mark( $order );

		self::assertGreaterThanOrEqual( $before + 3600, $stored );
		self::assertLessThanOrEqual( time() + 3600, $stored );
	}

	public function test_active_expiration_is_returned_as_int(): void {
		$expiresAt = time() + 60;

		self::assertSame( $expiresAt, $this->service->getActiveExpiration( $this->orderWithMark( (string) $expiresAt ) ) );
		self::assertTrue( $this->service->isActive( $this->orderWithMark( (string) $expiresAt ) ) );
	}

	public function test_mark_expiring_now_is_still_active(): void {
		$now = time();

		self::assertSame( $now, $this->service->getActiveExpiration( $this->orderWithMark( (string) $now ) ) );
	}

	/**
	 * @dataProvider inactiveMarks
	 *
	 * @param mixed $stored
	 */
	public function test_inactive_marks_return_null( $stored ): void {
		$order = $this->orderWithMark( $stored );

		self::assertNull( $this->service->getActiveExpiration( $order ) );
		self::assertFalse( $this->service->isActive( $order ) );
	}

	public function inactiveMarks(): array {
		return array(
			'absent'  => array( '' ),
			'expired' => array( (string) ( time() - 1 ) ),
			'garbage' => array( 'abc' ),
			'zero'    => array( '0' ),
		);
	}

	public function test_clear_deletes_existing_mark(): void {
		$order = Mockery::mock( 'WC_Order' );
		$order->allows( 'meta_exists' )->with( self::META_KEY )->andReturn( true );
		$order->expects( 'delete_meta_data' )->with( self::META_KEY );
		$order->expects( 'save_meta_data' );

		$this->service->clear( $order );
	}

	public function test_clear_does_nothing_without_mark(): void {
		$order = Mockery::mock( 'WC_Order' );
		$order->allows( 'meta_exists' )->with( self::META_KEY )->andReturn( false );
		$order->expects( 'delete_meta_data' )->never();
		$order->expects( 'save_meta_data' )->never();

		$this->service->clear( $order );
	}

	/**
	 * @param mixed $stored
	 * @return \Mockery\MockInterface&\WC_Order
	 */
	private function orderWithMark( $stored ) {
		$order = Mockery::mock( 'WC_Order' );
		$order->allows( 'get_meta' )->with( self::META_KEY, true )->andReturn( $stored );

		return $order;
	}
}
