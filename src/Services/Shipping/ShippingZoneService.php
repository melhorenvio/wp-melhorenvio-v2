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

			foreach ( $zone->get_zone_locations() as $location ) {
				if ( $location->type !== 'country' || $location->code !== 'BR' ) {
					continue;
				}

				foreach ( $zone->get_shipping_methods( false ) as $method ) {
					if ( $method->id === self::METHOD_ID ) {
						$zone->delete_shipping_method( $method->instance_id );
					}
				}
			}
		}
	}

	public function ensureMethodRegistered(): void {
		if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
			return;
		}

		$zone = $this->getBrazilZone();

		foreach ( $zone->get_shipping_methods( false ) as $method ) {
			if ( $method->id === self::METHOD_ID ) {
				return;
			}
		}

		$zone->add_shipping_method( self::METHOD_ID );
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
