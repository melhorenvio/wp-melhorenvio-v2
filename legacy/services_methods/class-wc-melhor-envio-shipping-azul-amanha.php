<?php

if ( class_exists( 'WC_Melhor_Envio_Shipping' ) ) {
	class WC_Melhor_Envio_Shipping_Azul_Amanha extends WC_Melhor_Envio_Shipping {

		const ID = 'melhorenvio_azul_amanha';

		const TITLE = 'Azul Amanhã';

		const METHOD_TITLE = 'Azul Amanhã (Melhor Envio)';

		public $code = 15;

		public $company = 'Azul Cargo Express';

		/**
		 * Initialize Azul Amanhã.
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
