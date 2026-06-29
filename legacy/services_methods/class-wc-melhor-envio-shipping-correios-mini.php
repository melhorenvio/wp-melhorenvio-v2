<?php

if ( class_exists( 'WC_Melhor_Envio_Shipping' ) ) {
	class WC_Melhor_Envio_Shipping_Correios_Mini extends WC_Melhor_Envio_Shipping {

		const ID = 'melhorenvio_correios_mini';

		const TITLE = 'Correios Mini';

		const METHOD_TITLE = 'Correios Mini (Melhor Envio)';

		public $code = 17;

		public $company = 'Correios';

		/**
		 * Initialize Correios Mini.
		 *
		 * @param int $instance_id Shipping zone instance.
		 */
		public function __construct( $instance_id = 0 ) {
			$this->id           = self::ID;
			$this->method_title = self::METHOD_TITLE;
			parent::__construct( $instance_id );
		}
	}
}
