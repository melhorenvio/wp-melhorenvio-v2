<?php

if ( class_exists( 'WC_Melhor_Envio_Shipping' ) ) {
	class WC_Melhor_Envio_Shipping_Via_Brasil_Rodoviario extends WC_Melhor_Envio_Shipping {

		const ID = 'melhorenvio_via_brasil_rodoviario';

		const TITLE = 'Via Brasil Rodoviário';

		const METHOD_TITLE = 'Via Brasil Rodoviário (Melhor Envio)';

		public $code = 9;

		public $company = 'Via Brasil';

		/**
		 * Initialize Via Brasil Rodoviário.
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
