<?php

namespace MelhorEnvio\Services;

class AgenciesService {

	const ROUTE_GET_AGENCIES = '/shipment/agencies';

	protected $serviceId = null;

	protected $state = null;

	protected $city = null;

	protected $company = null;

	protected $service = null;

	protected $latitude = null;

	protected $longitude = null;

	protected $distanceSearch = 100000;

	protected $limit = 100;

	protected $country = 'BR';

	protected $status = 'available';

	public function __construct( $data ) {
		if ( ! empty( $data['state'] ) ) {
			$this->state = $data['state'];
		}

		if ( ! empty( $data['city'] ) ) {
			$this->city = $data['city'];
		}

		if ( ! empty( $data['company'] ) ) {
			$this->company = $data['company'];
		}

		if ( ! empty( $data['serviceId'] ) ) {
			$this->service = $data['serviceId'];
		}

		if ( ! empty( $data['latitude'] ) && ! empty( $data['longitude'] ) ) {
			$this->latitude = $data['latitude'];
			$this->longitude = $data['longitude'];
			$this->state = null;
			$this->city = null;
		}
	}

	/**
	 * function to get  agencies on Melhor Envio API.
	 *
	 * @param array $data
	 * @return array
	 */
	public function get() {
		$route = $this->getRoute();
		$agencies = ( new RequestService() )->request(
			$route,
			'GET',
			array(),
			false
		);

		if ( empty( $agencies ) ) {
			return (object) array(
				'success' => false,
				'message' => 'Ocorreu um erro ao obter agências',
			);
		}

		return $this->normalize( $agencies );
	}

	/**
	 * @return string $route
	 */
	private function getRoute() {
		$data['status'] = $this->status;
		$data['country'] = $this->country;		
		$data['limit'] = $this->limit;

		if ( ! empty( $this->distanceSearch ) ) {
			$data['distanceSearch'] = $this->distanceSearch;
		}

		if ( ! empty( $this->state ) ) {
			$data['state'] = $this->state;
		}

		if ( ! empty( $this->city ) ) {
			$data['city'] = $this->city;
		}

		if ( ! empty( $this->company ) ) {
			$data['company'] = $this->company;
		}

		if ( ! empty( $this->service ) ) {
			$data['serviceId'] = $this->service;
		}

		if ( ! empty( $this->latitude ) && ! empty( $this->longitude ) ) {
			$data['latitude'] = $this->latitude;
			$data['longitude'] = $this->longitude;
			unset( $data['state'] );
			unset( $data['city'] );
		}

		$query = http_build_query( $data );

		return self::ROUTE_GET_AGENCIES . '?' . $query;
	}

	/**
	 * @param array $data
	 * @return array
	 */
	private function normalize( $data ) {
		$agencies = array();
		foreach ( $data as $agency ) {

			$companyId = null;

			if (!isset($agency->companies)){
				continue;
			}

			foreach ($agency->companies as $company) {

				$companyId = $company->id;

				if ( empty( $companyId ) ) {
					continue;
				}

				$agencies[ $companyId ][] = $agency;
			}
		}
		if ( ! empty( $this->company ) ) {
			if ( ! empty( $agencies[ $this->company ] ) ) {
				return $agencies[ $this->company ];
			}
		}

		return $agencies;
	}
}
