<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Support;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use MelhorEnvio\Support\Capabilities;
use MelhorEnvio\Tests\TestCase;

final class CapabilitiesTest extends TestCase {

	public function test_admin_defaults_to_manage_woocommerce(): void {
		self::assertSame( 'manage_woocommerce', Capabilities::admin() );
	}

	public function test_admin_uses_capability_from_filter(): void {
		Filters\expectApplied( 'melhorenvio_admin_capability' )
			->once()
			->with( 'manage_woocommerce' )
			->andReturn( 'edit_shop_orders' );

		self::assertSame( 'edit_shop_orders', Capabilities::admin() );
	}

	/**
	 * @dataProvider invalidFilteredCapabilities
	 * @param mixed $filtered
	 */
	public function test_admin_falls_back_to_default_when_filter_returns_invalid_value( $filtered ): void {
		Filters\expectApplied( 'melhorenvio_admin_capability' )->once()->andReturn( $filtered );

		self::assertSame( 'manage_woocommerce', Capabilities::admin() );
	}

	public function invalidFilteredCapabilities(): array {
		return array(
			'empty string' => array( '' ),
			'null'         => array( null ),
			'array'        => array( array( 'manage_options' ) ),
		);
	}

	/**
	 * @dataProvider accountCapabilityChecks
	 */
	public function test_can_manage_account_requires_manage_options( bool $userCan ): void {
		Functions\expect( 'current_user_can' )->once()->with( 'manage_options' )->andReturn( $userCan );

		self::assertSame( $userCan, Capabilities::canManageAccount() );
	}

	public function accountCapabilityChecks(): array {
		return array(
			'administrador'   => array( true ),
			'gerente de loja'  => array( false ),
		);
	}

	/**
	 * @dataProvider connectionCapabilityChecks
	 */
	public function test_can_use_connection_requires_default_capability_regardless_of_filter( array $userCaps, bool $expected ): void {
		Functions\when( 'apply_filters' )->justReturn( 'read' );
		Functions\when( 'current_user_can' )->alias(
			static function ( $capability ) use ( $userCaps ) {
				return in_array( $capability, $userCaps, true );
			}
		);

		self::assertSame( $expected, Capabilities::canUseConnection() );
	}

	public function connectionCapabilityChecks(): array {
		return array(
			'administrador'   => array( array( 'manage_options' ), true ),
			'gerente de loja' => array( array( 'manage_woocommerce' ), true ),
			'papel filtrado'  => array( array( 'read', 'edit_shop_orders' ), false ),
		);
	}
}
