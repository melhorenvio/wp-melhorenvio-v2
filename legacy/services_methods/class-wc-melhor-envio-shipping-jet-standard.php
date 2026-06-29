<?php

if ( class_exists( 'WC_Melhor_Envio_Shipping' ) ) {
	class WC_Melhor_Envio_Shipping_JeT_Standard extends WC_Melhor_Envio_Shipping {

		const ID = 'melhorenvio_jet_standard';

		const TITLE = 'JeT Standard';

		const METHOD_TITLE = 'JeT Standard (Melhor Envio)';

		public $code = 33;

		public $company = 'JeT';

		/**
		 * Initialize JeT Standard.
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
