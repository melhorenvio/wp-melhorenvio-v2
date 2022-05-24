<?php

namespace MelhorEnvio\Services;

use MelhorEnvio\Models\Method;
use MelhorEnvio\Models\Option;
use MelhorEnvio\Models\Payload;
use MelhorEnvio\Models\ShippingService;
use MelhorEnvio\Helpers\PostalCodeHelper;

class PayloadService {

	/**
	 * Function to save payload
	 *
	 * @param int $postId
	 * @return void
	 */
	public function save( $postId ) {
		$payload = $this->createPayloadCheckoutOrder( $postId );

		if ( ! empty( $payload ) ) {
			( new Payload() )->save( $postId, $payload );
		}
	}

	/**
	 * Function to return the payload data of the quote hiding customer data
	 *
	 * @param int $postId
	 * @return object
	 */
	public function getPayloadHideImportantData( $postId ) {
		$payload = ( new Payload() )->get( $postId );

		unset( $payload->seller );
		unset( $payload->buyer );

		if ( ! $this->validatePayload( $payload ) ) {
			return false;
		}

		return $payload;
	}

	/**
	 * Function to view payload to add item cart.
	 *
	 * @param int $postId
	 * @param int $methodId
	 * @return array
	 */
	public function getPayloadToCart( $postId, $methodId ) {
		$products = ( new OrdersProductsService() )->getProductsOrder( $postId );

		$buyer = ( new BuyerService() )->getDataBuyerByOrderId( $postId );

		$payload = ( new CartService() )->createPayloadToCart(
			$postId,
			$products,
			$buyer,
			$methodId
		);

		if ( ! $this->validatePayload( $payload ) ) {
			return false;
		}

		return $payload;
	}

	/**
	 * function to payload after finishied order in woocommerce.
	 *
	 * @param int $postId
	 * @return object
	 */
	public function createPayloadCheckoutOrder( $postId ) {
		$order          = new \WC_Order( $postId );
		$products       = ( new OrdersProductsService() )->getProductsOrder( $postId );
		$buyer          = ( new BuyerService() )->getDataBuyerByOrderId( $postId );
		$seller         = ( new SellerService() )->getData();
		$options        = ( new Option() )->getOptions();
		$productService = new ProductsService();
		$productsFilter = $productService->filter( $products );
		$serviceId      = ( new Method( $postId ) )->getMethodShipmentSelected( $postId );

		$payload = (object) array(
			'from'             => (object) array(
				'postal_code' => $seller->postal_code,
			),
			'to'               => (object) array(
				'postal_code' => $buyer->postal_code,
			),
			'services'         => implode( ',', ShippingService::getAvailableServices() ),
			'options'          => (object) array(
				'own_hand'            => $options->own_hand,
				'receipt'             => $options->receipt,
				'insurance_value'     => $order->get_subtotal(),
				'use_insurance_value' => $options->insurance_value,
			),
			'products'         => (object) $productsFilter,
			'service_selected' => $serviceId,
			'seller'           => $seller,
			'buyer'            => $buyer,
			'units'            => array(
				'weight'    => strtolower( get_option( 'woocommerce_weight_unit' ) ),
				'dimension' => strtolower( get_option( 'woocommerce_dimension_unit' ) ),
			),
			'shipping_total'   => $order->get_shipping_total(),
			'created'          => date( 'Y-m-d h:i:s' ),
		);

		if ( ! $this->validatePayload( $payload ) ) {
			return false;
		}

		return $payload;
	}

	/**
	 * function to create product-based payload
	 *
	 * @param string $postalCode
	 * @param array  $products
	 * @return object
	 */
	public function createPayloadByProducts( $postalCode, $products ) {
		$seller = ( new SellerService() )->getData();

		$options = ( new Option() )->getOptions();

		$productService = new ProductsService();

		$productsFilter = $productService->filter( $products );

		$payload = (object) array(
			'from'     => (object) array(
				'postal_code' => $seller->postal_code,
			),
			'to'       => (object) array(
				'postal_code' => $postalCode,
			),
			'services' => implode( ',', ShippingService::getAvailableServices() ),
			'options'  => (object) array(
				'own_hand'            => $options->own_hand,
				'receipt'             => $options->receipt,
				'insurance_value'     => $productService->getInsuranceValue( $productsFilter ),
				'use_insurance_value' => $options->insurance_value,
			),
			'products' => (object) $productsFilter,
		);

		if ( ! $this->validatePayload( $payload ) ) {
			return false;
		}

		return $payload;
	}

	/**
	 * function to remove options from the insured amount of the payload
	 *
	 * @param object $payload
	 * @return object
	 */
	public function removeInsuranceValue( $payload ) {
		$payload->products                 = ( new ProductsService() )->removePrice( (array) $payload->products );
		$payload->options->insurance_value = 0;
		$payload->services                 = implode(
			',',
			ShippingService::SERVICES_CORREIOS
		);

		return $payload;
	}

	/**
	 * Function to validate payload.
	 *
	 * @param object $payload
	 * @return bool
	 */
	public function validatePayload( $payload ) {
		if ( gettype( $payload ) != 'object' ) {
			return false;
		}

		if ( empty( $payload->from ) || empty( $payload->from->postal_code ) ) {
			return false;
		}

		$from = PostalCodeHelper::postalcode( $payload->from->postal_code );
		if ( strlen( $from ) != PostalCodeHelper::SIZE_POSTAL_CODE ) {
			return false;
		}

		if ( empty( $payload->to ) || empty( $payload->to->postal_code ) ) {
			return false;
		}

		$to = PostalCodeHelper::postalcode( $payload->to->postal_code );
		if ( strlen( $to ) != PostalCodeHelper::SIZE_POSTAL_CODE ) {
			return false;
		}

		if ( empty( $payload->options ) ) {
			return false;
		}

		if ( ! empty( $payload->products ) ) {
			foreach ( $payload->products as $product ) {
				if ( ! empty( $product->is_virtual ) ) {
					continue;
				}

				if ( ! $this->isProductValid( $product ) ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * validates if the payload product is valid.
	 *
	 * @param object $product
	 * @return bool
	 */
	private function isProductValid( $product ) {
		if ( empty( $product->name ) ) {
			return false;
		}

		if ( empty( $product->width ) ) {
			return false;
		}

		if ( empty( $product->height ) ) {
			return false;
		}

		if ( empty( $product->length ) ) {
			return false;
		}

		if ( empty( $product->weight ) ) {
			return false;
		}

		if ( ! isset( $product->unitary_value ) ) {
			return false;
		}

		if ( empty( $product->quantity ) ) {
			return false;
		}

		return true;
	}
}
