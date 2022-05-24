<?php

if ( class_exists( 'WC_Melhor_Envio_Shipping' ) ) {
	class WC_Melhor_Envio_Shipping_Correios_Pac extends WC_Melhor_Envio_Shipping {

		const ID = 'melhorenvio_correios_pac';

		const TITLE = 'Correios Pac';

		const METHOD_TITLE = 'Correios Pac (Melhor Envio)';

		public $code = 1;

		public $company = 'Correios';

		/**
		 * Initialize Correios Pac.
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
