<?php

namespace MelhorEnvio\Services;

use MelhorEnvio\Models\Method;
use MelhorEnvio\Models\ShippingService;
use MelhorEnvio\Services\QuotationService;

class OrderQuotationService {

	const POST_META_ORDER_QUOTATION = 'melhorenvio_quotation_v2';

	const POST_META_ORDER_DATA = 'melhorenvio_status_v2';

	const OPTION_TOKEN_ENVIRONMENT = 'wpmelhorenvio_token_environment';

	const DEFAULT_STRUCTURE_DATE = 'Y-m-d H:i:d';

	protected $env;

	public function __construct() {
		$this->env = $this->getEnvironmentToSave();
	}

	/**
	 * Function to get a quotation by order in postmetas by WordPress.
	 *
	 * @param integer $postId
	 * @return object $quotation
	 */
	public function getQuotation( $postId ) {
		$quotation = get_post_meta( $postId, self::POST_META_ORDER_QUOTATION );

		if ( ! $quotation || $this->isUltrapassedQuotation( $quotation ) ) {
			$quotation = ( new QuotationService() )->calculateQuotationByPostId( $postId );
		}

		$quotation = $this->checkHasCorreiosWithVolumes( $quotation );

		return $quotation;
	}

	/**
	 * Function to check the quote object and check if there are any
	 * Correios methods that contain volumes and remove that method.
	 *
	 * @param object $quotation
	 * @return object
	 */
	private function checkHasCorreiosWithVolumes( $quotation ) {
		$calculateShipping = new CalculateShippingMethodService();
		$shippingSelected  = $quotation['choose_method'];
		$shippingsRemoved  = array();

		foreach ( $quotation as $key => $item ) {
			if ( ! is_int( $key ) ) {
				continue;
			}

			if ( $calculateShipping->isCorreios( $key ) && $calculateShipping->hasMultipleVolumes( $item ) ) {
				$shippingsRemoved[] = $key;
				unset( $quotation[ $key ] );
			}
		}

		if ( $this->haveSelectedShippingInRemovedsShipping( $shippingsRemoved, $shippingSelected ) ) {
			$quotation['choose_method'] = $quotation[ array_key_first( $quotation ) ]->id;
		}

		return $quotation;
	}

	/**
	 * Function to check if the shipping method selected by the customer has been removed
	 *
	 * @param array $shippingsRemoved
	 * @param int   $shippingSelected
	 * @return boolean
	 */
	private function haveSelectedShippingInRemovedsShipping( $shippingsRemoved, $shippingSelected ) {
		return ( ! empty( $shippingsRemoved ) && in_array( $shippingSelected, $shippingsRemoved ) );
	}

	/**
	 * Save quotation in postmeta WordPress.
	 *
	 * @param int    $orderId
	 * @param object $quotation
	 * @return array $quotation
	 */
	public function saveQuotation( $orderId, $quotation ) {
		$methodId               = ( new OrderService() )->getMethodIdSelected( $orderId );
		$data                   = $this->setKeyAsCodeService( $quotation );
		$data['date_quotation'] = date( 'Y-m-d H:i:d' );
		$data['choose_method']  = ( ! empty( $methodId ) ) ? $methodId : ShippingService::CORREIOS_SEDEX;
		$data['free_shipping']  = false;
		$data['diff']           = is_null( $methodId );

		delete_post_meta( $orderId, self::POST_META_ORDER_QUOTATION );
		add_post_meta( $orderId, self::POST_META_ORDER_QUOTATION, $data, true );

		return $data;
	}

	/**
	 * Set a key of quotations array as code service.
	 *
	 * @param array $quotation
	 * @return array $quotationoid
	 */
	private function setKeyAsCodeService( $quotation ) {
		$result = array();

		if ( ! empty( $quotation ) ) {
			foreach ( $quotation as $item ) {
				if ( ! empty( $item->id ) ) {
					$result[ $item->id ] = $item;
					if ( isset( $item->packages ) ) {
						foreach ( $item->packages as $key => $package ) {
							if ( $package->weight == 0 ) {
								$result[ $item->id ]->packages[ $key ]->weight = 0.01;
							}
						}
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Get postmeta data by order (Status, orderId, protocol).
	 *
	 * @param int $orderId
	 * @return array $data
	 */
	public function getData( $orderId ) {
		return get_post_meta( $orderId, self::POST_META_ORDER_DATA . $this->env, true );
	}

	/**
	 * Function to update data quotation by order.
	 *
	 * @param int    $orderId
	 * @param string $orderMelhorEnvioId
	 * @param string $protocol
	 * @param string $status
	 * @param int    $chooseMethod
	 * @return array $data
	 */
	public function addDataQuotation(
		$orderId,
		$orderMelhorEnvioId,
		$protocol,
		$status,
		$chooseMethod,
		$purcahseId = null,
		$tracking = null
	) {
		$data = array(
			'choose_method' => $chooseMethod,
			'order_id'      => $orderMelhorEnvioId,
			'protocol'      => $protocol,
			'purchase_id'   => $purcahseId,
			'status'        => $status,
			'created'       => date( self::DEFAULT_STRUCTURE_DATE ),
		);

		add_post_meta( $orderId, self::POST_META_ORDER_DATA . $this->env, $data );

		return $data;
	}
	/**
	 * Function to update data quotation by order.
	 *
	 * @param int    $orderId
	 * @param string $order_melhor_envio_id
	 * @param string $protocol
	 * @param string $status
	 * @param int    $choose_method
	 * @return array $data
	 */
	public function updateDataQuotation(
		$orderId,
		$orderMelhorEnvioId,
		$protocol,
		$status,
		$chooseMethod,
		$purcahseId = null,
		$tracking = null
	) {
		$data = array(
			'choose_method' => $chooseMethod,
			'order_id'      => $orderMelhorEnvioId,
			'protocol'      => $protocol,
			'purchase_id'   => $purcahseId,
			'status'        => $status,
			'tracking'      => $tracking,
			'created'       => date( self::DEFAULT_STRUCTURE_DATE ),
		);

		delete_post_meta( $orderId, self::POST_META_ORDER_DATA . $this->env );
		add_post_meta( $orderId, self::POST_META_ORDER_DATA . $this->env, $data, true );

		return $data;
	}

	/**
	 * Function to delete the saved quote from an order
	 *
	 * @param int $orderId
	 */
	public function removeDataQuotation( $orderId ) {
		delete_post_meta( $orderId, self::POST_META_ORDER_DATA . $this->env );
	}

	/**
	 * Function to check if a quotation is ultrapassed.
	 *
	 * @param array $data
	 * @return boolean
	 */
	public function isUltrapassedQuotation( $data ) {
		if ( count( $data ) <= 4 ) {
			return true;
		}

		foreach ( $data as $item ) {
			if ( $item == 'Unauthenticated.' || empty( $item ) ) {
				return true;
			}
		}

		if ( ! isset( $data['date_quotation'] ) ) {
			return true;
		}

		$date = date( 'Y-m-d H:i:s', strtotime( '-3 day' ) );

		return ( $date > $data['date_quotation'] );
	}

	/**
	 * Function to return a prefix of environment.
	 *
	 * @return string $prefix_environment
	 */
	public function getEnvironmentToSave() {
		$environment = get_option( self::OPTION_TOKEN_ENVIRONMENT );

		return ( $environment == 'sandbox' ) ? '_sandbox' : null;
	}
}
