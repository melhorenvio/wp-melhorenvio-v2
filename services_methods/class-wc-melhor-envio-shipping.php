<?php

use MelhorEnvio\Services\CalculateShippingMethodService;
use MelhorEnvio\Helpers\MoneyHelper;

/**
 * Default Melhor Envio shipping method abstract class.
 *
 * This is a abstract method with default options for all methods.
 */

if ( class_exists( 'WC_Shipping_Method' ) ) {
	abstract class WC_Melhor_Envio_Shipping extends WC_Shipping_Method {

		/**
		 * Service code.
		 *
		 * @var string
		 */
		public $code = '';

		/**
		 * Company name.
		 *
		 * @var string
		 */
		public $company = '';

		/**
		 * Initialize the Melhor Envio shipping method.
		 *
		 * @param int $instance_id Shipping zone instance ID.
		 */
		public function __construct( $instance_id = 0 ) {
			$this->instance_id = absint( $instance_id );
			$this->service     = new CalculateShippingMethodService();
			$this->init_form_fields();
			$this->shipping_class_id  = (int) $this->get_option(
				'shipping_class_id',
				CalculateShippingMethodService::ANY_DELIVERY
			);
			$this->method_description = sprintf( 'Metódo de envio %s do Melhor Envio', $this->method_title );
			$this->title              = $this->get_option( 'title' );
			$this->additional_time    = $this->get_option( 'additional_time' );
			$this->additional_tax     = $this->get_option( 'additional_tax' );
			$this->percent_tax        = $this->get_option( 'percent_tax' );
			$this->supports           = array(
				'shipping-zones',
				'instance-settings',
				'instance-settings-modal',
			);

			add_action(
				'woocommerce_update_options_shipping_' . $this->id,
				array( $this, 'process_admin_options' )
			);
		}

		/**
		 * Admin options fields.
		 */
		function init_form_fields() {
			$this->instance_form_fields = array(
				'shipping_class_id' => array(
					'title'    => 'Classe de entrega',
					'type'     => 'select',
					'desc_tip' => true,
					'default'  => '',
					'class'    => 'wc-enhanced-select',
					'options'  => $this->service->getShippingClassesOptions(),
				),
				'title'             => array(
					'title'   => 'Título',
					'type'    => 'text',
					'default' => $this->method_title,
				),
				'additional_tax'    => array(
					'title'       => 'Taxa adicional',
					'type'        => 'text',
					'description' => 'Valor adicional sobre o valor do frete cobrado ao cliente final',
					'desc_tip'    => true,
					'default'     => '0',
					'placeholder' => '0',
				),
				'percent_tax'       => array(
					'title'       => 'Percentual de Taxa adicional',
					'type'        => 'text',
					'description' => 'Adiciona um percentual sobre o valor do frete cobrado ao cliente final',
					'desc_tip'    => true,
					'default'     => '0',
					'placeholder' => '0',
				),
				'additional_time'   => array(
					'title'       => 'Dias extras',
					'type'        => 'text',
					'description' => 'Adicional de dias no prazo final do frete',
					'desc_tip'    => true,
					'default'     => '0',
					'placeholder' => '0',
				),
			);
		}

		/**
		 * calculate_shipping function.
		 *
		 * @access public
		 * @param mixed $package
		 * @return void
		 */
		public function calculate_shipping( $package = array() ) {
			if ( ! $this->service->needShowShippginMethod( $package, $this->shipping_class_id ) ) {
				return;
			}

			$rate = $this->service->calculateShipping(
				$package,
				$this->code,
				$this->instance_id,
				$this->company,
				$this->title,
				( MoneyHelper::isDiscount( $this->additional_tax ) ) ? 0 - MoneyHelper::floatConverter( $this->additional_tax ) : MoneyHelper::floatConverter( $this->additional_tax ),
				intval( $this->additional_time ),
				( MoneyHelper::isDiscount( $this->percent_tax ) ) ? 0 - MoneyHelper::floatConverter( $this->percent_tax ) : MoneyHelper::floatConverter( $this->percent_tax )
			);

			if ( $rate ) {
				$this->add_rate( $rate );
			}
		}
	}
}
