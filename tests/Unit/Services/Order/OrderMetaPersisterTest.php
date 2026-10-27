<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Services\Order;

use Brain\Monkey\Filters;
use MelhorEnvio\Services\Order\OrderMetaPersister;
use MelhorEnvio\Tests\TestCase;
use Mockery;
use RuntimeException;

final class OrderMetaPersisterTest extends TestCase {

	private const FILTER = 'woocommerce_orders_table_datastore_should_save_after_meta_change';

	public function test_saves_meta_with_hpos_order_save_disabled_and_restores_filter(): void {
		Filters\expectAdded( self::FILTER )
			->once()
			->with(
				Mockery::on(
					static function ( $callback ): bool {
						return is_callable( $callback ) && false === $callback( true );
					}
				)
			);

		$filterActiveDuringSave = false;
		$order                  = Mockery::mock( 'WC_Order' );
		$order->expects( 'save_meta_data' )->andReturnUsing(
			static function () use ( &$filterActiveDuringSave ): void {
				$filterActiveDuringSave = false !== has_filter( self::FILTER );
			}
		);
		$order->expects( 'save' )->never();

		OrderMetaPersister::saveWithoutTouchingOrder( $order );

		self::assertTrue( $filterActiveDuringSave );
		self::assertFalse( has_filter( self::FILTER ) );
	}

	public function test_restores_filter_when_save_fails(): void {
		$order = Mockery::mock( 'WC_Order' );
		$order->expects( 'save_meta_data' )->andThrow( new RuntimeException( 'db' ) );

		try {
			OrderMetaPersister::saveWithoutTouchingOrder( $order );
			self::fail( 'A exceção do save_meta_data deveria propagar.' );
		} catch ( RuntimeException $exception ) {
			self::assertSame( 'db', $exception->getMessage() );
		}

		self::assertFalse( has_filter( self::FILTER ) );
	}

	public function test_does_not_remove_other_callbacks_on_the_same_filter(): void {
		// Assinatura diferente da closure interna: o Brain Monkey identifica closures pela
		// assinatura (o WordPress usa spl_object_id), e iguais se confundiriam no teste.
		$other = static function ( bool $shouldSave ): bool {
			return $shouldSave;
		};
		add_filter( self::FILTER, $other );

		$order = Mockery::mock( 'WC_Order' );
		$order->expects( 'save_meta_data' );

		OrderMetaPersister::saveWithoutTouchingOrder( $order );

		self::assertNotFalse( has_filter( self::FILTER, $other ) );
	}
}
