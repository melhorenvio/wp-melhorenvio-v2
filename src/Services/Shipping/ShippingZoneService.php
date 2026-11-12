<?php

declare(strict_types=1);

namespace MelhorEnvio\Services\Shipping;

final class ShippingZoneService {

	private const METHOD_ID = 'melhor_envio';

	public function removeMethod(): void {
		if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
			return;
		}

		foreach ( \WC_Shipping_Zones::get_zones() as $zoneData ) {
			$zone = new \WC_Shipping_Zone( $zoneData['id'] );

			foreach ( $zone->get_shipping_methods( false ) as $method ) {
				if ( $method->id === self::METHOD_ID ) {
					$zone->delete_shipping_method( $method->instance_id );
				}
			}
		}
	}

	public function ensureMethodRegistered(): void {
		if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
			return;
		}

		$zone = $this->getBrazilZone();

		if ( ! $this->hasMethod( $zone ) ) {
			$zone->add_shipping_method( self::METHOD_ID );
		}
	}

	public function ensureMethodInAllZones(): void {
		if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
			return;
		}

		$this->ensureMethodRegistered();

		foreach ( \WC_Shipping_Zones::get_zones() as $zoneData ) {
			$zone = new \WC_Shipping_Zone( $zoneData['id'] );

			if ( $this->coversBrazil( $zone ) && ! $this->hasMethod( $zone ) ) {
				$zone->add_shipping_method( self::METHOD_ID );
			}
		}
	}

	private function hasMethod( \WC_Shipping_Zone $zone ): bool {
		foreach ( $zone->get_shipping_methods( false ) as $method ) {
			if ( $method->id === self::METHOD_ID ) {
				return true;
			}
		}

		return false;
	}

	private function coversBrazil( \WC_Shipping_Zone $zone ): bool {
		$hasRegion = false;

		foreach ( $zone->get_zone_locations() as $location ) {
			if (
				( $location->type === 'country' && $location->code === 'BR' )
				|| ( $location->type === 'state' && strpos( $location->code, 'BR:' ) === 0 )
				|| ( $location->type === 'continent' && $location->code === 'SA' )
			) {
				return true;
			}

			$hasRegion = $hasRegion || $location->type !== 'postcode';
		}

		return ! $hasRegion;
	}

	private function getBrazilZone(): \WC_Shipping_Zone {
		foreach ( \WC_Shipping_Zones::get_zones() as $zoneData ) {
			$zone = new \WC_Shipping_Zone( $zoneData['id'] );

			foreach ( $zone->get_zone_locations() as $location ) {
				if ( $location->type === 'country' && $location->code === 'BR' ) {
					return $zone;
				}
			}
		}

		$zone = new \WC_Shipping_Zone();
		$zone->set_zone_name( 'Brasil' );
		$zone->add_location( 'BR', 'country' );
		$zone->save();

		return $zone;
	}
}
