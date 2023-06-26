<?php

if ( class_exists( 'WC_Melhor_Envio_Shipping' ) ) {
	class WC_Melhor_Envio_Shipping_Jadlog_Package_Centralized extends WC_Melhor_Envio_Shipping {

		const ID = 'melhorenvio_jadlog_package_centralized';

		const TITLE = 'Jadlog Package Centralizado';

		const METHOD_TITLE = 'Jadlog Package Centralizado (Melhor Envio)';

		public $code = 27;

		public $company = 'Jadlog';

		/**
		 * Initialize Jadlog Package.
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
