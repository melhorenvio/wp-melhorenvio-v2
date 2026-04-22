<?php

namespace MelhorEnvio\Services;

/**
 * Health service class
 */
class CheckHealthService {

	public function init() {
		$this->hasShippingMethodsMelhorEnvio();
	}

	/**
	 * Function to display message to the user if he does not
	 * have selected shipping methods
	 *
	 * @return void
	 */
	public function hasShippingMethodsMelhorEnvio() {
		add_action(
			'woocommerce_init',
			function () {
				$methods = ( new ShippingMelhorEnvioService() )
				->getMethodsActivedsMelhorEnvio();

				if ( count( $methods ) == 0 ) {
					$message = sprintf(
						'<div class="error">
                    <h2>Atenção usuário do Plugin Melhor Envio</h2>
                        <p>%s</p>
                    </div>',
						'Por favor, verificar os métodos de envios do Melhor Envio na tela de <a href="/wp-admin/admin.php?page=wc-settings&tab=shipping">configurações de áreas de entregas do WooCommerce</a> após a instalação da versão <b>2.8.0</b>. Devido a nova funcionalidade de classes de entrega, é necessário selecionar novamente os métodos de envios do Melhor Envio.'
					);

					( new SessionNoticeService() )->add(
						$message,
						SessionNoticeService::NOTICE_INFO
					);
				}
			}
		);
	}

	/**
	 * Function to check if the plugin has all the necessary plugins to run.
	 *
	 * @param string $pathPlugins
	 * @return array
	 */
	public function checkPathPlugin( $pathPlugins ) {
		$errorsPath = array();

        $sessionNoticeService = new SessionNoticeService();
        $sessionNoticeService->removeNoticesContaning( 'woocommerce-extra-checkout-fields-for-brazil' );
        $sessionNoticeService->removeNoticesContaning( 'woo-better-shipping-calculator-for-brazil' );

		if ( ! is_dir( $pathPlugins . '/woocommerce' ) ) {
			$errorsPath[] = 'Defina o path do diretório de plugins nas configurações do plugin do Melhor Envio';
		}

		$errors          = array();
		$pluginsActiveds = apply_filters(
			'network_admin_active_plugins',
			get_option( 'active_plugins' )
		);

		if ( ! class_exists( 'WooCommerce' ) ) {
			$errors[] = 'Você precisa do plugin WooCommerce ativado no WordPress para utilizar o plugin do Melhor Envio';
		}

		$hasBetterShipping = in_array( 'woo-better-shipping-calculator-for-brazil/wc-better-shipping-calculator-for-brazil.php', $pluginsActiveds );
		$hasExtraCheckout  = in_array( 'woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php', $pluginsActiveds );

		if ( ! $hasBetterShipping && ! $hasExtraCheckout && ! is_multisite() ) {
			$errors[] = 'O plugin do Melhor Envio <strong>necessita obrigatoriamente</strong> de um dos seguintes plugins instalado e ativado para funcionar corretamente: <a target="_blank" href="https://br.wordpress.org/plugins/woo-better-shipping-calculator-for-brazil/">Calculadora de Frete e Campos Checkout para o Brasil</a> ou <a target="_blank" href="https://br.wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/">Brazilian Market on WooCommerce</a>.';
		}

		if ( ! empty( $errors ) ) {
			foreach ( $errors as $err ) {
				$sessionNoticeService->add( $err, SessionNoticeService::NOTICE_INFO );
			}
		}

		return array(
			'errors'     => $errors,
			'errorsPath' => $errorsPath,
		);
	}
}
