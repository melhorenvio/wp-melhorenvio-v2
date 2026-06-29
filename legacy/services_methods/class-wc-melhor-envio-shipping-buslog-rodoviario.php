<?php

use MelhorEnvio\Models\ShippingService;

if ( class_exists( 'WC_Melhor_Envio_Shipping' ) ) {
	class WC_Melhor_Envio_Shipping_Buslog_Rodoviario extends WC_Melhor_Envio_Shipping {

		const ID = 'melhorenvio_buslog_rodoviario';

		const TITLE = 'Buslog Rodoviário';

		const METHOD_TITLE = 'Buslog Rodoviário (Melhor Envio)';

		public $code = ShippingService::BUSLOG_RODOVIARIO;

		public $company = 'Buslog';

		/**
		 * Initialize Buslog Rodoviário.
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
