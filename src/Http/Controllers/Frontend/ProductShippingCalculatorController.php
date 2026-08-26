<?php

declare(strict_types=1);

namespace MelhorEnvio\Http\Controllers\Frontend;

use MelhorEnvio\Services\Admin\PluginModeService;

final class ProductShippingCalculatorController {

	public function register(): void {
		if ( ! PluginModeService::isIntegradorMode() ) {
			return;
		}

		if ( get_option( 'melhorenvio_hide_calculator_product' ) ) {
			return;
		}

		if ( empty( get_option( 'melhor_envio_integrador_quotation_token' ) ) ) {
			return;
		}

		$hook = get_option( 'melhor_envio_option_where_show_calculator' )
			?: 'woocommerce_before_add_to_cart_button';

		add_action( $hook, array( $this, 'renderWidget' ) );
	}

	public function renderWidget(): void {
		if ( ! is_product() ) {
			return;
		}

		global $product;

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		if ( $product->is_virtual() ) {
			return;
		}

		wp_enqueue_style(
			'me-shipping-calculator',
			MELHORENVIO_URL . '/assets/css/me-shipping-calculator.css',
			array(),
			MELHORENVIO_VERSION
		);

		wp_enqueue_script(
			'me-shipping-calculator',
			MELHORENVIO_URL . '/assets/js/me-shipping-calculator.js',
			array( 'jquery' ),
			MELHORENVIO_VERSION,
			true
		);

		wp_localize_script(
			'me-shipping-calculator',
			'meSC',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'me_quote' ),
				'productId' => $product->get_id(),
			)
		);

		?>
		<div id="me-cep-calc" class="me-cep-calc" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>">
			<h4 class="me-cep-calc__title"><?php esc_html_e( 'Calcular frete', 'melhor-envio-cotacao' ); ?></h4>
			<input
				type="text"
				id="me-cep-input"
				class="me-cep-calc__input input-text woocommerce-Input woocommerce-Input--text"
				placeholder="00000-000"
				maxlength="9"
				inputmode="numeric"
			/>
			<div id="me-cep-result" class="me-cep-calc__result"></div>
		</div>
		<?php
	}
}
