<?php

if ( class_exists( 'WC_Melhor_Envio_Shipping' ) ) {
	class WC_Melhor_Envio_Shipping_Latam_Juntos extends WC_Melhor_Envio_Shipping {

		const ID = 'melhorenvio_latam_juntos';

		const TITLE = 'LATAM Cargo éFácil';

		const METHOD_TITLE = 'LATAM Cargo éFácil (Melhor Envio)';

		public $code = 12;

		public $company = 'LATAM Cargo';

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
