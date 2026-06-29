<?php

if ( class_exists( 'WC_Melhor_Envio_Shipping' ) ) {
	class WC_Melhor_Envio_Shipping_Loggi_Express extends WC_Melhor_Envio_Shipping {

		const ID = 'melhorenvio_loggi_express';

		const TITLE = 'Loggi Express';

		const METHOD_TITLE = 'Loggi Express (Melhor Envio)';

		public $code = 31;

		public $company = 'Loggi';

		/**
		 * Initialize Latam.
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
