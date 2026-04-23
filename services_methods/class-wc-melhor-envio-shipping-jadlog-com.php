<?php

if ( class_exists( 'WC_Melhor_Envio_Shipping' ) ) {
	class WC_Melhor_Envio_Shipping_Jadlog_Com extends WC_Melhor_Envio_Shipping {

		const ID = 'melhorenvio_jadlog_com';

		const TITLE = 'Jadlog .Com';

		const METHOD_TITLE = 'Jadlog .Com (Melhor Envio)';

		public $code = 4;

		public $company = array(
			'name' 		=> 'Jadlog',
			'document' 	=> '05.011.552/0001-65'
		);

		/**
		 * Initialize Jadlog .COm.
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
