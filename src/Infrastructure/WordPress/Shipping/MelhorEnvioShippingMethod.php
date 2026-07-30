<?php

declare(strict_types=1);

namespace MelhorEnvio\Infrastructure\WordPress\Shipping;

use MelhorEnvio\Infrastructure\WordPress\Http\MelhorEnvioApiClient;
use WC_Shipping_Method;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MelhorEnvioShippingMethod extends WC_Shipping_Method {

	private MelhorEnvioApiClient $apiClient;
	private CartItemsBuilder $cartItemsBuilder;

	public function __construct( int $instance_id = 0 ) {
		parent::__construct( $instance_id );

		$this->id                 = 'melhor_envio';
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = __( 'Melhor Envio', 'melhor-envio-cotacao' );
		$this->method_description = __( 'Cotação automática via Melhor Envio.', 'melhor-envio-cotacao' );
		$this->supports           = array( 'shipping-zones', 'instance-settings' );
		$this->enabled            = 'yes';
		$this->title              = $this->get_option( 'title', __( 'Melhor Envio', 'melhor-envio-cotacao' ) );

		$this->init();
		$this->apiClient        = new MelhorEnvioApiClient();
		$this->cartItemsBuilder = new CartItemsBuilder();
	}

	public function init(): void {
		$this->init_form_fields();
		$this->init_settings();

		add_action(
			'woocommerce_update_options_shipping_' . $this->id,
			array( $this, 'process_admin_options' )
		);
	}

	public function init_form_fields(): void {
		$this->form_fields = array(
			'title' => array(
				'title'   => __( 'Título', 'melhor-envio-cotacao' ),
				'type'    => 'text',
				'default' => __( 'Melhor Envio', 'melhor-envio-cotacao' ),
			),
		);
	}

	public function calculate_shipping( $package = array() ): void {
		$fromCep = preg_replace( '/\D/', '', get_option( 'woocommerce_store_postcode', '' ) );
		$toCep   = preg_replace( '/\D/', '', $package['destination']['postcode'] ?? '' );

		if ( empty( $fromCep ) || empty( $toCep ) ) {
			wc_get_logger()->warning(
				sprintf( 'calculate_shipping abortado: CEP ausente. from=%s to=%s', $fromCep, $toCep ),
				array( 'source' => 'melhor-envio-cotacao' )
			);
			return;
		}

		$items    = $this->cartItemsBuilder->buildItems();
		$cacheKey = 'me_quote_' . md5( $toCep . serialize( $items ) );
		$cached   = get_transient( $cacheKey );

		if ( $cached !== false ) {
			$quotations = $cached;
		} else {
			$quotations = $this->apiClient->getQuotations( $fromCep, $toCep, $items );
			set_transient( $cacheKey, $quotations, 30 * MINUTE_IN_SECONDS );
		}

		foreach ( $quotations as $service ) {
			$deliveryTime = $service['custom_delivery_time'] ?? $service['delivery_time'] ?? null;
			$price        = $service['custom_price'] ?? $service['price'] ?? 0;

			$serviceName = $service['name'] ?? __( 'Melhor Envio', 'melhor-envio-cotacao' );
			$company     = $service['company']['name'] ?? '';
			$label       = $company !== '' ? sprintf( '%s - %s', $company, $serviceName ) : $serviceName;

			if ( $deliveryTime !== null ) {
				$label .= sprintf( ' (%d dias úteis)', (int) $deliveryTime );
			}

			$this->add_rate(
				array(
					'id'        => $this->id . '_' . ( $service['id'] ?? uniqid() ),
					'label'     => $label,
					'cost'      => (float) $price,
					'meta_data' => array(
						'me_service_id'    => (string) ( $service['id'] ?? '' ),
						'me_service_name'  => $serviceName,
						'me_company_id'    => (string) ( $service['company']['id'] ?? 0 ),
						'me_company_name'  => $company,
						'me_delivery_time' => (string) ( $deliveryTime ?? 0 ),
					),
				)
			);
		}
	}
}
