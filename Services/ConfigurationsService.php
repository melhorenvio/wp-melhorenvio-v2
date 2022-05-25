<?php

namespace MelhorEnvio\Services;

use MelhorEnvio\Models\Address;
use MelhorEnvio\Models\Agency;
use MelhorEnvio\Models\Option;
use MelhorEnvio\Models\CalculatorShow;
use MelhorEnvio\Models\ShippingCompany;

class ConfigurationsService {

	const WIDTH_DEFAULT = 10;

	const HEIGHT_DEFAULT = 10;

	const LENGTH_DEFAULT = 10;

	const WEIGHT_DEFAULT = 11;


	const FIELDS_ADDRESS = array(
		'id',
		'address',
		'complement',
		'number',
		'district',
		'city',
		'state',
		'country_id',
		'postal_code',
	);

	/**
	 * Function to save the salesperson's settings.
	 *
	 * @param array $data
	 * @return array
	 */
	public function saveConfigurations( $data ) {
		$response = array();

		( new ClearDataStored() )->clear();

		if ( isset( $data['origin'] ) ) {
			$response['origin'] = ( new Address() )->setAddressShopping(
				$data['origin']
			);
		}

		if ( isset( $data['dimension_default'] ) ) {
			$response['dimension_default'] = $this->setDimensionDefault(
				$data['dimension_default']
			);
		}

		if ( isset( $data['agency'] ) ) {
			$response['agency'][ ShippingCompany::JADLOG ] = $data['agency'];
		}

		if ( isset( $data['agency_azul'] ) ) {
			$response['agency'][ ShippingCompany::AZUL_CARGO ] = $data['agency_azul'];
		}

		if ( isset( $data['agency_latam'] ) ) {
			$response['agency'][ ShippingCompany::LATAM_CARGO ] = $data['agency_latam'];
		}

		if ( ! empty( $response['agency'] ) ) {
			( new AgenciesSelectedService() )->set( $response['agency'] );
		}

		if ( isset( $data['show_calculator'] ) ) {
			$response['show_calculator'] = ( new CalculatorShow() )->set(
				$data['show_calculator']
			);
		}

		if ( isset( $data['where_calculator'] ) ) {
			$response['where_calculator'] = $this->setWhereCalculator(
				$data['where_calculator']
			);
		}

		if ( isset( $data['path_plugins'] ) ) {
			$response['path_plugins'] = $this->savePathPlugins(
				$data['path_plugins']
			);
		}

		if ( isset( $data['options_calculator'] ) ) {
			$response['options_calculator'] = $this->setOptionsCalculator(
				$data['options_calculator']
			);
		}

		if ( isset( $data['label'] ) ) {
			$response['label'] = $this->setLabel(
				$data['label']
			);
		}

		return $response;
	}

	/**
	 * Function to search all user settings
	 *
	 * @return array
	 */
	public function getConfigurations() {
		$token = ( new TokenService() )->get();

		$origin = $this->getAddresses();

		$originselected = $this->getOriginSelected( $origin );

		$agencies          = array();
		$agenciesSelecteds = array();
		if ( ! empty( $originselected ) ) {
			$address           = array(
				'state'   => $originselected['address']['state'],
				'city'    => $originselected['address']['city'],
				'company' => null,
			);
			$agencies          = ( new AgenciesService( $address ) )->get();
			$agenciesSelecteds = ( new AgenciesSelectedService() )->get();
		}

		return array(
			'origin'              => $origin,
			'label'               => $this->getLabel( $origin ),
			'agencies'            => $this->filterAgenciesByCompany(
				$agencies,
				ShippingCompany::JADLOG
			),
			'agencySelected'      => $this->filterAgencySelectedByCompany(
				$agenciesSelecteds,
				ShippingCompany::JADLOG
			),
			'agenciesAzul'        => $this->filterAgenciesByCompany(
				$agencies,
				ShippingCompany::AZUL_CARGO
			),
			'agencyAzulSelected'  => $this->filterAgencySelectedByCompany(
				$agenciesSelecteds,
				ShippingCompany::AZUL_CARGO
			),
			'agenciesLatam'       => $this->filterAgenciesByCompany(
				$agencies,
				ShippingCompany::LATAM_CARGO
			),
			'agencyLatamSelected' => $this->filterAgencySelectedByCompany(
				$agenciesSelecteds,
				ShippingCompany::LATAM_CARGO
			),
			'calculator'          => ( new CalculatorShow() )->get(),
			'where_calculator'    => ( ! get_option( 'melhor_envio_option_where_show_calculator' ) )
				? 'woocommerce_before_add_to_cart_button'
				: get_option( 'melhor_envio_option_where_show_calculator' ),
			'path_plugins'        => $this->getPathPluginsArray(),
			'options_calculator'  => $this->getOptionsCalculator(),
			'token_environment'   => $token['token_environment'],
			'dimension_default'   => $this->getDimensionDefault(),
		);
	}

	/**
	 * @param array $agencies
	 * @param int   $companyId
	 * @return array
	 */
	private function filterAgenciesByCompany( $agencies, $companyId ) {
		return ( isset( $agencies[ $companyId ] ) )
			? $agencies[ $companyId ]
			: array();
	}

	/**
	 * @param array $agenciesSelecteds
	 * @param int   $companyId
	 * @return array
	 */
	private function filterAgencySelectedByCompany( $agenciesSelecteds, $companyId ) {
		return ( isset( $agenciesSelecteds[ $companyId ] ) )
			? $agenciesSelecteds[ $companyId ]
			: null;
	}

	/**
	 * @param array $origin
	 * @return array
	 */
	private function getOriginSelected( $origin ) {
		$originselected = null;
		foreach ( $origin as $item ) {
			if ( $item['selected'] ) {
				$originselected = $item;
			}
		}
		return $originselected;
	}




	/**
	 * Function to save the moment that will be called the product calculator
	 *
	 * @param string $option
	 * @return void
	 */
	public function setWhereCalculator( $option ) {
		delete_option( 'melhor_envio_option_where_show_calculator' );
		add_option( 'melhor_envio_option_where_show_calculator', $option );

		return array(
			'success' => true,
			'option'  => $option,
		);
	}

	/**
	 * @param array $label
	 * @return array
	 */
	public function setLabel( $label ) {
		delete_option( 'melhor_envio_option_label' );
		add_option( 'melhor_envio_option_label', $label );

		return array(
			'success' => true,
			'option'  => $label,
		);
	}

	/**
	 * @param array $dimension
	 * @return array
	 */
	public function setDimensionDefault( $dimension ) {
		delete_option( 'melhor_envio_option_dimension_default' );
		add_option( 'melhor_envio_option_dimension_default', $dimension );

		return array(
			'success' => true,
			'option'  => $dimension,
		);
	}

	/**
	 * Function to save receipt and own hands options
	 *
	 * @param array $options
	 * @return array
	 */
	public function setOptionsCalculator( $options ) {
		delete_option( Option::OPTION_RECEIPT );
		delete_option( Option::OPTION_OWN_HAND );
		delete_option( Option::OPTION_INSURANCE_VALUE );

		add_option( Option::OPTION_RECEIPT, $options['receipt'], true );
		add_option( Option::OPTION_OWN_HAND, $options['own_hand'], true );
		add_option( Option::OPTION_INSURANCE_VALUE, $options['insurance_value'], true );

		return array(
			'success' => true,
			'options' => $this->getOptionsCalculator(),
		);
	}

	/**
	 * Function to save the plugin's directory path
	 *
	 * @param stroing $path
	 * @return string
	 */
	public function savePathPlugins( $path ) {
		delete_option( 'melhor_envio_path_plugins' );
		add_option( 'melhor_envio_path_plugins', $path );

		return get_option( 'melhor_envio_path_plugins' );
	}

	/**
	 * Function for obtaining acknowledgment options and own hands
	 *
	 * @return array
	 */
	public function getOptionsCalculator() {
		return array(
			'receipt'         => filter_var( get_option( Option::OPTION_RECEIPT, 'false' ), FILTER_VALIDATE_BOOLEAN ),
			'own_hand'        => filter_var( get_option( Option::OPTION_OWN_HAND, 'false' ), FILTER_VALIDATE_BOOLEAN ),
			'insurance_value' => filter_var( get_option( Option::OPTION_INSURANCE_VALUE, 'true' ), FILTER_VALIDATE_BOOLEAN ),
		);
	}

	/**
	 * Function to get path plugin.
	 *
	 * @return string
	 */
	public function getPathPluginsArray() {
		$path = get_option( 'melhor_envio_path_plugins' );

		if ( ! $path ) {
			$path = WP_PLUGIN_DIR;
		}

		return $path;
	}

	public function getAddresses() {
		$addressSelectedId = ( new Address() )->getSelectedAddressId();

		$addresses = ( new Address() )->getAddressesShopping();

		$addresses = ( ! empty( $addresses['addresses'] ) )
			? $addresses['addresses']
			: array();

		$stores = ( new StoreService() )->getStores();

		$sellerData = ( new SellerService() )->getDataApiMelhorEnvio();

		$response = array();

		if ( ! empty( $addresses ) ) {
			foreach ( $addresses as $address ) {
				$response[ $address['id'] ] = $this->getPersonalAddress( $address, $sellerData, $addressSelectedId );
			}
		}

		if ( ! empty( $stores ) ) {
			foreach ( $stores as $store ) {
				if ( ! empty( $store->address ) ) {
					$storeAddress = $this->getStoreAddress( $store, $sellerData, $addressSelectedId );
					foreach ( $storeAddress as $item ) {
						$response[ $item['id'] ] = $item;
					}
				}
			}
		}

		return array_values( $response );
	}

	/**
	 * @param array  $address
	 * @param object $sellerData
	 * @param int    $addressSelectedId
	 * @return array
	 */
	private function getPersonalAddress( $address, $sellerData, $addressSelectedId ) {
		return array(
			'id'                     => $address['id'],
			'name'                   => sprintf( '%s %s', $sellerData->firstname, $sellerData->lastname ),
			'email'                  => $sellerData->email,
			'phone'                  => $sellerData->phone->phone,
			'document'               => ( ! empty( $sellerData->document ) )
				? $sellerData->document
				: '',
			'company_document'       => ( ! empty( $sellerData->company_document ) )
				? $sellerData->company_document
				: '',
			'state_register'         => ( ! empty( $sellerData->state_register ) )
				? $sellerData->state_register
				: '',
			'economic_activity_code' => ( ! empty( $sellerData->economic_activity_code ) )
				? $sellerData->economic_activity_code
				: '',
			'type'                   => 'address',
			'address'                => $address,
			'selected'               => ( $address['id'] == $addressSelectedId ),
		);
	}

	/**
	 * @param object $store
	 * @param object $sellerData
	 * @param int    $addressSelectedId
	 * @return array
	 */
	private function getStoreAddress( $store, $sellerData, $addressSelectedId ) {
		$addresses = array();
		if ( ! empty( $store->address ) ) {
			foreach ( $store->address as $address ) {
				$addresses[] = array(
					'id'                     => $address->id,
					'name'                   => $store->name,
					'email'                  => $store->email,
					'phone'                  => $sellerData->phone->phone,
					'company_document'       => ! empty( $store->document )
						? $store->document
						: '',
					'state_register'         => ! empty( $store->state_register )
						? $store->state_register
						: '',
					'economic_activity_code' => ! empty( $store->economic_activity_code )
						? $store->economic_activity_code
						: '',
					'type'                   => 'store',
					'address'                => array(
						'id'          => $address->id,
						'address'     => $address->address,
						'complement'  => $address->complement,
						'label'       => $address->label,
						'postal_code' => $address->postal_code,
						'number'      => $address->number,
						'district'    => $address->district,
						'city'        => $address->city->city,
						'state'       => $address->city->state->state_abbr,
						'country'     => 'BR',
					),
					'selected'               => $address->id == $addressSelectedId,
				);
			}
		}

		return $addresses;
	}

	/**
	 * @param array $origin
	 * @return array
	 */
	public function getDimensionDefault() {
		$dimension = get_option( 'melhor_envio_option_dimension_default' );
		if ( empty( $dimension ) ) {
			return array(
				'width'  => self::WIDTH_DEFAULT,
				'height' => self::HEIGHT_DEFAULT,
				'length' => self::LENGTH_DEFAULT,
				'weight' => self::WEIGHT_DEFAULT,
			);
		}

		return $dimension;
	}

	/**
	 * @return array
	 */
	public function getLabel( $origin = null ) {
		$labelOption = get_option( 'melhor_envio_option_label' );

		if ( ! empty( $labelOption ) ) {
			return $this->normalizeDataSeller( $labelOption );
		}

		$label = null;
		if ( ! empty( $origin ) ) {
			foreach ( $origin as $item ) {
				if ( $item['selected'] ) {
					$address = $item['address'];
					unset( $item['address'] );
					unset( $item['selected'] );
					unset( $item['type'] );
					$label = $item;
					foreach ( self::FIELDS_ADDRESS as $field ) {
						if ( isset( $address[ $field ] ) ) {
							$label[ $field ] = $address[ $field ];
						}
					}
				}
			}
		}

		return $this->normalizeDataSeller( $label );
	}

	/**
	 * @param array $seller
	 * @return array
	 */
	private function normalizeDataSeller( $seller ) {
		if ( is_array( ( $seller ) ) ) {
			foreach ( $seller as $key => $value ) {
				if ( is_int( $key ) ) {
					unset( $seller[ $key ] );
				}
				if ( $value == 'undefined' || empty( $value ) || $value == 'null' ) {
					$seller[ $key ] = '';
				}
			}
		}
		return $seller;
	}
}
