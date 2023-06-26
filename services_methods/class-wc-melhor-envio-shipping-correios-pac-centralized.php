<?php

if ( class_exists( 'WC_Melhor_Envio_Shipping' ) ) {
	class WC_Melhor_Envio_Shipping_Correios_Pac_Centralized extends WC_Melhor_Envio_Shipping {

		const ID = 'melhorenvio_correios_pac_centralized';

		const TITLE = 'Correios Pac Centralizado';

		const METHOD_TITLE = 'Correios Pac Centralizado (Melhor Envio)';

		public $code = 28;

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
