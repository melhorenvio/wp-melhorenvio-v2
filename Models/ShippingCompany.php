<?php

namespace MelhorEnvio\Models;

class ShippingCompany {

	const CORREIOS = 1;

	const JADLOG = 2;

	const LATAM_CARGO = 6;

	const AZUL_CARGO = 9;

	/**
	 * @param int $serviceId
	 * @return int
	 */
	public static function getCompanyIdByService( $serviceId ) {
		if ( in_array( $serviceId, ShippingService::SERVICES_JADLOG ) ) {
			return self::JADLOG;
		}

		if ( in_array( $serviceId, ShippingService::SERVICES_LATAM ) ) {
			return self::LATAM_CARGO;
		}

		if ( in_array( $serviceId, ShippingService::SERVICES_AZUL ) ) {
			return self::AZUL_CARGO;
		}

		return null;
	}
}
