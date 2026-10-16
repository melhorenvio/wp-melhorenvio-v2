<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Services\Shipping;

use MelhorEnvio\Services\Shipping\ShippingZoneService;
use MelhorEnvio\Tests\TestCase;

final class ShippingZoneServiceTest extends TestCase {

	private ShippingZoneService $service;

	protected function setUp(): void {
		parent::setUp();
		$this->service = new ShippingZoneService();
	}

	public function test_remove_method_deletes_melhor_envio_from_brazil_zone(): void {
		$this->givenZone(
			1,
			array( $this->location( 'country', 'BR' ) ),
			array( $this->method( 'flat_rate', 3 ), $this->method( 'melhor_envio', 7 ) )
		);

		$this->service->removeMethod();

		self::assertSame( array( 7 ), $this->instanceFor( 1 )->deleted_method_instance_ids );
	}

	public function test_remove_method_deletes_every_melhor_envio_instance(): void {
		$this->givenZone(
			1,
			array( $this->location( 'country', 'BR' ) ),
			array( $this->method( 'melhor_envio', 7 ), $this->method( 'melhor_envio', 9 ) )
		);

		$this->service->removeMethod();

		self::assertSame( array( 7, 9 ), $this->instanceFor( 1 )->deleted_method_instance_ids );
	}

	public function test_remove_method_ignores_zones_that_are_not_brazil(): void {
		$this->givenZone(
			1,
			array( $this->location( 'country', 'PT' ), $this->location( 'state', 'BR' ), $this->location( 'postcode', 'BR' ) ),
			array( $this->method( 'melhor_envio', 7 ) )
		);

		$this->service->removeMethod();

		self::assertSame( array(), $this->instanceFor( 1 )->deleted_method_instance_ids );
	}

	public function test_remove_method_handles_multiple_zones(): void {
		$this->givenZone( 1, array( $this->location( 'country', 'AR' ) ), array( $this->method( 'melhor_envio', 4 ) ) );
		$this->givenZone( 2, array( $this->location( 'country', 'BR' ) ), array( $this->method( 'melhor_envio', 5 ) ) );

		$this->service->removeMethod();

		self::assertSame( array(), $this->instanceFor( 1 )->deleted_method_instance_ids );
		self::assertSame( array( 5 ), $this->instanceFor( 2 )->deleted_method_instance_ids );
	}

	public function test_remove_method_does_nothing_without_zones(): void {
		$this->service->removeMethod();

		self::assertSame( array(), \WC_Shipping_Zone::$instances );
	}

	public function test_ensure_method_registered_adds_method_to_existing_brazil_zone(): void {
		$this->givenZone( 1, array( $this->location( 'country', 'BR' ) ), array( $this->method( 'flat_rate', 3 ) ) );

		$this->service->ensureMethodRegistered();

		$zone = $this->instanceFor( 1 );
		self::assertSame( array( 'melhor_envio' ), $zone->added_methods );
		self::assertFalse( $zone->saved );
		self::assertCount( 1, \WC_Shipping_Zone::$instances );
	}

	public function test_ensure_method_registered_skips_when_method_already_present(): void {
		$this->givenZone( 1, array( $this->location( 'country', 'BR' ) ), array( $this->method( 'melhor_envio', 7 ) ) );

		$this->service->ensureMethodRegistered();

		self::assertSame( array(), $this->instanceFor( 1 )->added_methods );
	}

	public function test_ensure_method_registered_picks_brazil_zone_among_others(): void {
		$this->givenZone( 1, array( $this->location( 'country', 'PT' ) ), array() );
		$this->givenZone( 2, array( $this->location( 'state', 'BR:SP' ), $this->location( 'country', 'BR' ) ), array() );

		$this->service->ensureMethodRegistered();

		self::assertSame( array(), $this->instanceFor( 1 )->added_methods );
		self::assertSame( array( 'melhor_envio' ), $this->instanceFor( 2 )->added_methods );
	}

	public function test_ensure_method_registered_creates_brazil_zone_when_missing(): void {
		$this->givenZone( 1, array( $this->location( 'country', 'PT' ) ), array() );

		$this->service->ensureMethodRegistered();

		$created = end( \WC_Shipping_Zone::$instances );
		self::assertNull( $created->get_id() );
		self::assertSame( 'Brasil', $created->get_zone_name() );
		self::assertEquals( array( $this->location( 'country', 'BR' ) ), $created->get_zone_locations() );
		self::assertTrue( $created->saved );
		self::assertSame( array( 'melhor_envio' ), $created->added_methods );
		self::assertSame( array(), $this->instanceFor( 1 )->added_methods );
	}

	public function test_ensure_method_registered_creates_brazil_zone_when_no_zones_exist(): void {
		$this->service->ensureMethodRegistered();

		self::assertCount( 1, \WC_Shipping_Zone::$instances );
		self::assertSame( 'Brasil', \WC_Shipping_Zone::$instances[0]->get_zone_name() );
		self::assertSame( array( 'melhor_envio' ), \WC_Shipping_Zone::$instances[0]->added_methods );
	}

	/**
	 * @param object[] $locations
	 * @param object[] $methods
	 */
	private function givenZone( int $id, array $locations, array $methods ): void {
		\WC_Shipping_Zone::$records[ $id ] = array(
			'locations' => $locations,
			'methods'   => $methods,
		);
		\WC_Shipping_Zones::$zones[] = array( 'id' => $id );
	}

	private function location( string $type, string $code ): object {
		return (object) array(
			'code' => $code,
			'type' => $type,
		);
	}

	private function method( string $id, int $instanceId ): object {
		return (object) array(
			'id'          => $id,
			'instance_id' => $instanceId,
		);
	}

	private function instanceFor( int $id ): \WC_Shipping_Zone {
		foreach ( \WC_Shipping_Zone::$instances as $instance ) {
			if ( $instance->get_id() === $id ) {
				return $instance;
			}
		}

		self::fail( "No WC_Shipping_Zone instance created for zone {$id}." );
	}
}
