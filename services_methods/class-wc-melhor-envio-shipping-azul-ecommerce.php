<?php

if ( class_exists( 'WC_Melhor_Envio_Shipping' ) ) {
	class WC_Melhor_Envio_Shipping_Azul_Ecommerce extends WC_Melhor_Envio_Shipping {

		const ID = 'melhorenvio_azul_ecommerce';

		const TITLE = 'Azul Ecommerce';

		const METHOD_TITLE = 'Azul Ecommerce (Melhor Envio)';

		public $code = 16;

		public $company = 'Azul Cargo Express';

		/**
		 * Initialize Azul Ecommerce.
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
