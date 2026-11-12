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

	public function test_remove_method_deletes_melhor_envio_from_zones_that_are_not_brazil(): void {
		$this->givenZone(
			1,
			array( $this->location( 'state', 'BR:BA' ) ),
			array( $this->method( 'flat_rate', 3 ), $this->method( 'melhor_envio', 7 ) )
		);

		$this->service->removeMethod();

		self::assertSame( array( 7 ), $this->instanceFor( 1 )->deleted_method_instance_ids );
	}

	public function test_remove_method_handles_multiple_zones(): void {
		$this->givenZone( 1, array( $this->location( 'country', 'AR' ) ), array( $this->method( 'melhor_envio', 4 ) ) );
		$this->givenZone( 2, array( $this->location( 'country', 'BR' ) ), array( $this->method( 'melhor_envio', 5 ) ) );
		$this->givenZone( 3, array( $this->location( 'country', 'PT' ) ), array( $this->method( 'flat_rate', 6 ) ) );

		$this->service->removeMethod();

		self::assertSame( array( 4 ), $this->instanceFor( 1 )->deleted_method_instance_ids );
		self::assertSame( array( 5 ), $this->instanceFor( 2 )->deleted_method_instance_ids );
		self::assertSame( array(), $this->instanceFor( 3 )->deleted_method_instance_ids );
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

	public function test_ensure_method_in_all_zones_adds_method_to_brazil_and_state_zones(): void {
		$this->givenZone( 1, array( $this->location( 'country', 'BR' ) ), array() );
		$this->givenZone( 2, array( $this->location( 'state', 'BR:BA' ) ), array() );

		$this->service->ensureMethodInAllZones();

		self::assertSame( 1, $this->methodCount( 1 ) );
		self::assertSame( 1, $this->methodCount( 2 ) );
	}

	public function test_ensure_method_in_all_zones_skips_zone_with_method_even_if_disabled(): void {
		$this->givenZone( 1, array( $this->location( 'country', 'BR' ) ), array( $this->method( 'melhor_envio', 7 ) ) );
		$this->givenZone( 2, array( $this->location( 'state', 'BR:BA' ) ), array( $this->method( 'melhor_envio', 8, false ) ) );

		$this->service->ensureMethodInAllZones();

		self::assertSame( 1, $this->methodCount( 1 ) );
		self::assertSame( 1, $this->methodCount( 2 ) );
	}

	public function test_ensure_method_in_all_zones_is_idempotent(): void {
		$this->givenZone( 1, array( $this->location( 'country', 'BR' ) ), array() );
		$this->givenZone( 2, array( $this->location( 'state', 'BR:BA' ) ), array() );
		$this->givenZone( 3, array( $this->location( 'postcode', '40000-000...48999-999' ) ), array() );

		$this->service->ensureMethodInAllZones();
		$this->service->ensureMethodInAllZones();

		self::assertSame( 1, $this->methodCount( 1 ) );
		self::assertSame( 1, $this->methodCount( 2 ) );
		self::assertSame( 1, $this->methodCount( 3 ) );
	}

	/**
	 * @dataProvider brazilLocations
	 */
	public function test_ensure_method_in_all_zones_adds_method_to_zones_covering_brazil( string $type, string $code ): void {
		$this->givenZone( 1, array( $this->location( 'country', 'BR' ) ), array() );
		$this->givenZone( 2, array( $this->location( 'country', 'PT' ), $this->location( $type, $code ) ), array() );

		$this->service->ensureMethodInAllZones();

		self::assertSame( 1, $this->methodCount( 2 ) );
	}

	public function brazilLocations(): array {
		return array(
			'state'     => array( 'state', 'BR:SP' ),
			'continent' => array( 'continent', 'SA' ),
			'postcode'  => array( 'postcode', '40000-000' ),
		);
	}

	/**
	 * @dataProvider foreignLocations
	 */
	public function test_ensure_method_in_all_zones_ignores_zones_outside_brazil( array $locations ): void {
		$this->givenZone( 1, array( $this->location( 'country', 'BR' ) ), array() );
		$this->givenZone( 2, $locations, array() );

		$this->service->ensureMethodInAllZones();

		self::assertSame( 0, $this->methodCount( 2 ) );
	}

	public function foreignLocations(): array {
		return array(
			'portugal'  => array( array( $this->location( 'country', 'PT' ) ) ),
			'argentina' => array( array( $this->location( 'country', 'AR' ), $this->location( 'state', 'AR:C' ) ) ),
			'europe'    => array( array( $this->location( 'continent', 'EU' ) ) ),
			'empty'     => array( array() ),
		);
	}

	public function test_ensure_method_in_all_zones_creates_brazil_zone_when_no_zones_exist(): void {
		$this->service->ensureMethodInAllZones();

		self::assertCount( 1, \WC_Shipping_Zone::$instances );
		self::assertSame( 'Brasil', \WC_Shipping_Zone::$instances[0]->get_zone_name() );
		self::assertTrue( \WC_Shipping_Zone::$instances[0]->saved );
		self::assertSame( array( 'melhor_envio' ), \WC_Shipping_Zone::$instances[0]->added_methods );
	}

	public function test_ensure_method_in_all_zones_creates_brazil_zone_when_only_state_zones_exist(): void {
		$this->givenZone( 1, array( $this->location( 'state', 'BR:BA' ) ), array() );

		$this->service->ensureMethodInAllZones();

		$created = array_values(
			array_filter(
				\WC_Shipping_Zone::$instances,
				static function ( \WC_Shipping_Zone $zone ): bool {
					return $zone->get_id() === null;
				}
			)
		);
		self::assertCount( 1, $created );
		self::assertSame( 'Brasil', $created[0]->get_zone_name() );
		self::assertSame( array( 'melhor_envio' ), $created[0]->added_methods );
		self::assertSame( 1, $this->methodCount( 1 ) );
	}

	public function test_ensure_method_in_all_zones_keeps_other_methods(): void {
		$this->givenZone( 1, array( $this->location( 'country', 'BR' ) ), array( $this->method( 'flat_rate', 3 ) ) );
		$this->givenZone( 2, array( $this->location( 'state', 'BR:BA' ) ), array( $this->method( 'free_shipping', 4 ) ) );

		$this->service->ensureMethodInAllZones();

		self::assertSame( array( 'flat_rate', 'melhor_envio' ), $this->methodIds( 1 ) );
		self::assertSame( array( 'free_shipping', 'melhor_envio' ), $this->methodIds( 2 ) );
		foreach ( \WC_Shipping_Zone::$instances as $zone ) {
			self::assertSame( array(), $zone->deleted_method_instance_ids );
		}
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

	private function method( string $id, int $instanceId, bool $enabled = true ): object {
		return (object) array(
			'id'          => $id,
			'instance_id' => $instanceId,
			'enabled'     => $enabled ? 'yes' : 'no',
		);
	}

	private function methodIds( int $zoneId ): array {
		return array_map(
			static function ( object $method ): string {
				return $method->id;
			},
			\WC_Shipping_Zone::$records[ $zoneId ]['methods']
		);
	}

	private function methodCount( int $zoneId ): int {
		return count(
			array_filter(
				$this->methodIds( $zoneId ),
				static function ( string $id ): bool {
					return $id === 'melhor_envio';
				}
			)
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
