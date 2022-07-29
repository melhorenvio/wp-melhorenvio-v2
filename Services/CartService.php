<?php

namespace MelhorEnvio\Services;

use MelhorEnvio\Models\Order;
use MelhorEnvio\Models\Option;
use MelhorEnvio\Models\Payload;
use MelhorEnvio\Models\Session;
use MelhorEnvio\Models\ShippingCompany;
use MelhorEnvio\Helpers\SessionHelper;
use MelhorEnvio\Helpers\PostalCodeHelper;
use MelhorEnvio\Helpers\CpfHelper;
use MelhorEnvio\Helpers\ProductVirtualHelper;

class CartService {

	const PLATAFORM = 'WooCommerce V2';

	const ROUTE_MELHOR_ENVIO_ADD_CART = '/cart';

	/**
	 * Function to add item on Cart Melhor Envio
	 *
	 * @param int   $orderId
	 * @param array $products
	 * @param array $dataBuyer
	 * @param int   $shippingMethodId
	 * @return array
	 */
	public function add( $orderId, $products, $dataBuyer, $shippingMethodId ) {

		$body = $this->createPayloadToCart( $orderId, $products, $dataBuyer, $shippingMethodId );

		$errors = $this->validatePayloadBeforeAddCart( $body, $orderId );

		if ( ! empty( $errors ) ) {
			return array(
				'success' => false,
				'errors'  => $errors,
			);
		}

		$result = ( new RequestService() )->request(
			self::ROUTE_MELHOR_ENVIO_ADD_CART,
			'POST',
			$body,
			true
		);

		if ( ! empty( $result->errors ) ) {
			return array(
				'success' => false,
				'errors'  => end( $result->errors ),
			);
		}

		if ( empty( $result->id ) ) {
			return array(
				'success' => false,
				'errors'  => 'Não foi possível enviar o pedido para o carrinho de compras',
			);
		}

		return ( new OrderQuotationService() )->updateDataQuotation(
			$orderId,
			$result->id,
			$result->protocol,
			Order::STATUS_PENDING,
			$shippingMethodId,
			null,
			$result->self_tracking
		);
	}

	/**
	 * Function to create payload to insert item on Cart Melhor Envio
	 *
	 * @param int   $orderId
	 * @param array $products
	 * @param array $dataBuyer
	 * @param int   $shippingMethodId
	 * @return array
	 */
	public function createPayloadToCart( $orderId, $products, $dataBuyer, $shippingMethodId ) {
		$products = ProductVirtualHelper::removeVirtuals( $products );

		$dataFrom = ( new SellerService() )->getData();

		$quotation = ( new QuotationService() )->calculateQuotationByPostId( $orderId );

		$orderInvoiceService = new OrderInvoicesService();

		$methodService = new CalculateShippingMethodService();

		$options = ( new Option() )->getOptions();

		$insuranceRequired = ( $methodService->isCorreios( $shippingMethodId ) )
			? $methodService->insuranceValueIsRequired( $options->insurance_value, $shippingMethodId )
			: true;

		$insuranceValue = ( ! empty( $insuranceRequired ) )
			? ( new ProductsService() )->getInsuranceValue( $products )
			: 0;

		$payload = array(
			'from'     => $dataFrom,
			'to'       => $dataBuyer,
			'agency'   => $this->getAgencyToInsertCart( $shippingMethodId ),
			'service'  => $shippingMethodId,
			'products' => $products,
			'volumes'  => $this->getVolumes( $quotation, $shippingMethodId ),
			'options'  => array(
				'insurance_value' => $insuranceValue,
				'receipt'         => $options->receipt,
				'own_hand'        => $options->own_hand,
				'collect'         => false,
				'reverse'         => false,
				'non_commercial'  => $orderInvoiceService->isNonCommercial( $orderId ),
				'invoice'         => $orderInvoiceService->getInvoiceOrder( $orderId ),
				'platform'        => self::PLATAFORM,
				'reminder'        => null,
			),
		);

		return $payload;
	}

	/**
	 * function to get agency selected by service_Id
	 *
	 * @param string $shippingMethodId
	 * @return int|null
	 */
	private function getAgencyToInsertCart( $shippingMethodId ) {
		$companyId         = ShippingCompany::getCompanyIdByService( $shippingMethodId );
		$agenciesSelecteds = ( new AgenciesSelectedService() )->get();
		if ( ! empty( $agenciesSelecteds[ $companyId ] ) ) {
			return $agenciesSelecteds[ $companyId ];
		}

		return null;
	}

	/**
	 * Function to remove order in cart by Melhor Envio.
	 *
	 * @param int    $postId
	 * @param string $orderId
	 * @return bool
	 */
	public function remove( $postId, $orderId ) {
		( new OrderQuotationService() )->removeDataQuotation( $postId );

		( new RequestService() )->request(
			self::ROUTE_MELHOR_ENVIO_ADD_CART . '/' . $orderId,
			'DELETE',
			array()
		);

		$orderInCart = ( new OrderService() )->info( $orderId );

		if ( ! $orderInCart['success'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Mount array with volumes by products.
	 *
	 * @param array $quotation
	 * @param int   $methodid
	 * @return array $volumes
	 */
	private function getVolumes( $quotation, $methodId ) {
		$volumes = array();

		foreach ( $quotation as $item ) {
			if ( ! isset( $item->id ) ) {
				continue;
			}
			if ( $item->id == $methodId ) {
				foreach ( $item->packages as $package ) {
					$volumes[] = array(
						'height' => $package->dimensions->height,
						'width'  => $package->dimensions->width,
						'length' => $package->dimensions->length,
						'weight' => $package->weight,
					);
				}
			}
		}

		if ( ( new CalculateShippingMethodService() )->isCorreios( $methodId ) ) {
			return $volumes[0];
		}

		return $volumes;
	}

	/**
	 * Function to validate params before send to request
	 *
	 * @param array $body
	 * @return array
	 */
	private function validatePayloadBeforeAddCart( $body, $orderId ) {
		$errors = array();

		if ( empty( $body['service'] ) ) {
			$errors[] = 'Informar o serviço de envio.';
		}

		$isCorreios = ( new CalculateShippingMethodService() )->isCorreios( $body['service'] );
		$errors     = array_merge( $errors, $this->validateAddress( 'from', 'remetente', $body, $isCorreios ) );
		$errors     = array_merge( $errors, $this->validateAddress( 'to', 'destinatario', $body, $isCorreios ) );

		if ( $this->isAgencyNecessary( $body['service'] ) && empty( $body['agency'] ) ) {
			$errors[] = 'É necessário informar a agência de postagem para esse serviço de envio';
		}

		if ( empty( $body['products'] ) ) {
			$errors[] = 'É necessário informar os produtos do envio.';
		}

		if ( ! empty( $body['products'] ) ) {
			foreach ( $body['products'] as $key => $product ) {
				$index = $key++;

				if ( empty( $product->name ) ) {
					$errors[] = sprintf( 'Infomar o nome do produto %d', $index );
				}

				if ( empty( $product->quantity ) ) {
					$errors[] = sprintf( 'Infomar a quantidade do produto %d', $index );
				}

				if ( empty( $product->unitary_value ) ) {
					$errors[] = sprintf( 'Infomar o valor unitário do produto %d', $index );
				}

				if ( empty( $product->weight ) ) {
					$errors[] = sprintf( 'Infomar o peso do produto %d', $index );
				}

				if ( empty( $product->width ) ) {
					$errors[] = sprintf( 'Infomar a largura do produto %d', $index );
				}

				if ( empty( $product->height ) ) {
					$errors[] = sprintf( 'Infomar a altura do produto %d', $index );
				}

				if ( empty( $product->length ) ) {
					$errors[] = sprintf( 'Infomar o comprimento do produto %d', $index );
				}
			}
		}

		if ( empty( $body['options'] ) ) {
			$errors[] = 'Informar os opcionais do envio.';
		}

		if ( empty( $body['options'] ) ) {
			$errors[] = 'Informar os opcionais do envio.';
		}

		if ( empty( $body['volumes'] ) ) {
			$errors[] = 'Informar o(s) volume(s) do envio.';
			return $errors;
		}

		if ( empty( $body['volumes'][0] ) ) {
			$errors = $this->validateVolume( $body['volumes'], $errors );
		}

		if ( ! empty( $body['volumes'][0] ) ) {
			foreach ( $body['volumes'] as $volume ) {
				$errors = $this->validateVolume( $volume, $errors );
			}
		}

		return $errors;
	}

	/**
	 * @param int $service
	 * @return bool
	 */
	private function isAgencyNecessary( $service ) {
		$calculateShippingMethodService = new CalculateShippingMethodService();

		$isAzulCargo = $calculateShippingMethodService->isAzulCargo( $service );

		$isLatamCargo = $calculateShippingMethodService->isLatamCargo( $service );

		return ( $isAzulCargo || $isLatamCargo );
	}

	/**
	 * Function to validate volume
	 *
	 * @param array $volume
	 * @param array $errors
	 * @return array
	 */
	private function validateVolume( $volume, $errors ) {
		if ( ! empty( $volume ) ) {
			if ( empty( $volume['height'] ) ) {
				$errors[] = 'Informar a altura do volume.';
			}

			if ( empty( $volume['width'] ) ) {
				$errors[] = 'Informar a largura do volume.';
			}

			if ( empty( $volume['length'] ) ) {
				$errors[] = 'Informar o comprimento do volume.';
			}

			if ( empty( $volume['weight'] ) ) {
				$errors[] = 'Informar o peso do volume.';
			}
		}

		return $errors;
	}

	/**
	 * Function to validate date address createPayloadToCart
	 *
	 * @param string $key
	 * @param string $user
	 * @param array  $body
	 * @param bool   $isCorreios
	 * @return array
	 */
	private function validateAddress( $key, $user, $body, $isCorreios ) {
		$errors = array();

		if ( empty( $body[ $key ] ) ) {
			$errors[] = "Informar o {$user} o pedido.";
		}

		if ( ! empty( $body[ $key ] ) && empty( $body[ $key ]->name ) ) {
			$errors[] = "Informar o nome do {$user} do pedido.";
		}

		if ( ! empty( $body[ $key ] ) && empty( $body[ $key ]->phone ) && ! $isCorreios ) {
			$errors[] = "Informar o telefone do {$user} do pedido.";
		}

		if ( ! empty( $body[ $key ] ) && empty( $body[ $key ]->email ) && ! $isCorreios ) {
			$errors[] = "Informar o e-mail do {$user} do pedido.";
		}

		if ( ! empty( $body[ $key ] ) && ( empty( $body[ $key ]->document ) && empty( $body[ $key ]->company_document ) ) && ! $isCorreios ) {
			$errors[] = "Informar o documento do {$user} do pedido.";
		}

		if ( ! empty( $body[ $key ] ) && empty( $body[ $key ]->address ) ) {
			$errors[] = "Informar o endereço do {$user} do pedido.";
		}

		if ( ! empty( $body[ $key ] ) && ( ! isset( $body[ $key ]->number ) || $body[ $key ] == '' ) ) {
			$errors[] = "Informar o número do endereço do {$user} do pedido.";
		}

		if ( ! empty( $body[ $key ] ) && empty( $body[ $key ]->city ) ) {
			$errors[] = "Informar a cidade do {$user} do pedido.";
		}

		if ( ! empty( $body[ $key ] ) && empty( $body[ $key ]->state_abbr ) ) {
			$errors[] = "Informar o estado do {$user} do pedido.";
		}

		if ( empty( $body[ $key ]->postal_code ) ) {
			$errors[] = "Informar o CEP do {$user} do pedido.";
		}

		if ( ! empty( $body[ $key ]->postal_code ) ) {
			$body[ $key ]->postal_code = PostalCodeHelper::postalcode( $body[ $key ]->postal_code );
			if ( strlen( $body[ $key ]->postal_code ) != PostalCodeHelper::SIZE_POSTAL_CODE ) {
				$errors[] = "CEP do {$user} incorreto.";
			}
		}

		return $errors;
	}

	/**
	 * Function to get data and information about items in the shopping cart
	 *
	 * @return array
	 */
	public function getInfoCart() {
		SessionHelper::initIfNotExists();

		global $woocommerce;

		$data = array();

		foreach ( $woocommerce->cart->get_cart() as $cart ) {
			foreach ( $cart as $item ) {
				if ( gettype( $item ) == 'object' ) {
					$productId = $item->get_id();
					if ( ! empty( $productId ) ) {
						$data['products'][ $productId ] = array(
							'name'  => $item->get_name(),
							'price' => $item->get_price(),
						);

						if ( ! empty( $_SESSION[ Session::ME_KEY ]['melhorenvio_additional'] ) ) {
							foreach ( $_SESSION[ Session::ME_KEY ]['melhorenvio_additional'] as $dataSession ) {
								foreach ( $dataSession as $keyProduct => $product ) {
									$data['products'][ $productId ]['taxas_extras'] = $product;
								}
							}
						}
					}
				}
			}
		}

		if ( ! empty( $_SESSION[ Session::ME_KEY ]['melhorenvio_additional'] ) ) {
			$data['adicionais_extras'] = $_SESSION[ Session::ME_KEY ]['melhorenvio_additional'];
		}

		return $data;
	}
}
