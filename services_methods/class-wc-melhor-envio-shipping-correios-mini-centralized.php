<?php

if ( class_exists( 'WC_Melhor_Envio_Shipping' ) ) {
	class WC_Melhor_Envio_Shipping_Correios_Mini_Centralized extends WC_Melhor_Envio_Shipping {

		const ID = 'melhorenvio_correios_mini_centralized';

		const TITLE = 'Correios Mini Centralizado';

		const METHOD_TITLE = 'Correios Mini Centralizado (Melhor Envio)';

		public $code = 30;

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
