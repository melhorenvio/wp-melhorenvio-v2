<?php

namespace MelhorEnvio\Controllers;

use MelhorEnvio\Helpers\SanitizeHelper;
use MelhorEnvio\Services\PayloadService;
use MelhorEnvio\Services\QuotationService;
use MelhorEnvio\Services\QuotationProductPageService;
use MelhorEnvio\Models\Session;

/**
 * Class responsible for the quotation controller
 */
class QuotationController {

	/**
	 * Construct of CotationController
	 */
	public function __construct() {
		add_action(
			'woocommerce_checkout_order_processed',
			array(
				$this,
				'makeCotationOrder',
			)
		);
	}

	/**
	 * Function to make a quotation by order woocommerce
	 *
	 * @param int $postId
	 * @return void
	 */
	public function makeCotationOrder( $postId ) {
		$result = ( new QuotationService() )->calculateQuotationByPostId( $postId );

		if ( $result ) {
			( new PayloadService() )->save( $postId );
		}

		if ( ! empty( $_SESSION[ Session::ME_KEY ]['quotation'] ) ) {
			unset( $_SESSION[ Session::ME_KEY ]['quotation'] );
		}

		return $result;
	}

	/**
	 * Function to refresh quotation
	 *
	 * @return json
	 */
	public function refreshCotation() {
		$results = $this->makeCotationOrder( SanitizeHelper::apply( $_GET['id'] ) );
		return wp_send_json(
			$results,
			200
		);
	}

	/**
	 * Function to perform the quotation on the product calculator
	 *
	 * @return json
	 */
	public function cotationProductPage() {
		$data = SanitizeHelper::apply( $_POST['data'] );

		$this->isValidRequest( $data );

		$rates = ( new QuotationProductPageService(
			intval( $data['id_produto'] ),
			$data['cep_origem'],
			$data['quantity']
		) )->getRatesShipping();

		if ( ! empty( $rates['error'] ) ) {
			return wp_send_json(
				array(
					'success' => false,
					'error'   => $rates['error'],
				),
				500
			);
		}

		return wp_send_json(
			array(
				'success' => true,
				'data'    => $rates,
			),
			200
		);
	}

	/**
	 * Function to validate request in screen product
	 *
	 * @param array $data
	 * @return json
	 */
	private function isValidRequest( $data ) {
		if ( ! isset( $data['cep_origem'] ) ) {
			return wp_send_json(
				array(
					'success' => false,
					'message' => 'Infomar CEP de origem',
				),
				400
			);
		}
	}

	/**
	 * @param [type] $package
	 * @param [type] $services
	 * @param [type] $to
	 * @param array  $options
	 * @return void
	 */
	public function makeCotationPackage( $package, $services, $to, $options = array() ) {
		return $this->makeCotation( $to, $services, array(), $package, $options, false );
	}
}

$quotationController = new QuotationController();
