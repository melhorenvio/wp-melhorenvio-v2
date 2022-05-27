<?php

namespace MelhorEnvio\Controllers;

use MelhorEnvio\Helpers\SanitizeHelper;
use MelhorEnvio\Helpers\WpNonceValidatorHelper;
use MelhorEnvio\Models\Address;
use MelhorEnvio\Models\Agency;
use MelhorEnvio\Models\Store;
use MelhorEnvio\Models\Method;
use MelhorEnvio\Models\Option;
use MelhorEnvio\Services\ConfigurationsService;
use MelhorEnvio\Services\OptionsMethodShippingService;

/**
 * Class responsible for the configuration controller
 */
class ConfigurationController {

	/**
	 * Function to get configurations of user
	 *
	 * @return json
	 */
	public function getConfigurations() {

		WpNonceValidatorHelper::check( $_GET['_wpnonce'], 'save_configurations' );

		return wp_send_json(
			( new ConfigurationsService() )->getConfigurations(),
			200
		);
	}

	/**
	 * Function to obtain which hook the calculator will
	 * be displayed on the product screen
	 *
	 * @return string
	 */
	public function getWhereCalculatorValue() {
		$option = get_option( 'melhor_envio_option_where_show_calculator' );
		if ( ! $option ) {
			return 'woocommerce_before_add_to_cart_button';
		}
		return $option;
	}

	/**
	 * Function to save all configs
	 *
	 * @param Array $data
	 * @return json
	 */
	public function saveAll() {

		WpNonceValidatorHelper::check( $_POST['_wpnonce'], 'save_configurations' );

		$response = ( new ConfigurationsService() )->saveConfigurations( SanitizeHelper::apply( $_POST ) );
		return wp_send_json( $response, 200 );
	}
}
