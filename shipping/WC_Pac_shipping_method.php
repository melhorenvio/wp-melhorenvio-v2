<?php

namespace Shipping;

use Abstracts\MelhorEnvioAbstract;

/**
 * Metodo de envio Correios PAC
 */
class WC_Pac_shipping_method extends MelhorEnvioAbstract {

	protected $code = '1';
	/**
	 * Initialize PAC.
	 *
	 * @param int $instance_id Shipping zone instance.
	 */
	public function __construct( $instance_id = 0 ) {

		$this->id           = 'correios-pac';
		$this->method_title = 'PAC';
		$this->title = 'PAC';

		parent::__construct( $instance_id );
	}

	/**
	 * Get the declared value from the package.
	 *
	 * @param  array $package Cart package.
	 * @return float
	 */
	protected function get_declared_value( $package ) {
		if ( 18 >= $package['contents_cost'] ) {
			return 0;
		}
		return $package['contents_cost'];
	}

}
