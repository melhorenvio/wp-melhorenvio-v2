<?php

if ( class_exists( 'WC_Melhor_Envio_Shipping' ) ) {
	class WC_Melhor_Envio_Shipping_Loggi_Coleta extends WC_Melhor_Envio_Shipping {

		const ID = 'melhorenvio_loggi_coleta';

		const TITLE = 'Loggi Coleta';

		const METHOD_TITLE = 'Loggi Coleta (Melhor Envio)';

		public $code = 32;

		public $company = 'Loggi';

		/**
		 * Initialize Loggi.
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
