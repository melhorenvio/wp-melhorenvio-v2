<?php

declare(strict_types=1);

namespace MelhorEnvio\Infrastructure\WordPress\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class OrderInvoiceKeyMetaBox {

	public const META_KEY = '_me_invoice_key';

	private const NONCE_ACTION = 'me_save_invoice_key';
	private const NONCE_FIELD  = 'me_invoice_key_nonce';
	private const FIELD_NAME   = 'me_invoice_key';

	public function register(): void {
		add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'render' ) );
		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'save' ) );
	}

	public function render( \WC_Order $order ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		$value = $order->get_meta( self::META_KEY, true );
		?>
		<p class="form-field">
			<label for="<?php echo esc_attr( self::FIELD_NAME ); ?>">
				<?php esc_html_e( 'Chave da nota fiscal', 'melhor-envio-cotacao' ); ?>
			</label>
			<input
				type="text"
				class="widefat"
				id="<?php echo esc_attr( self::FIELD_NAME ); ?>"
				name="<?php echo esc_attr( self::FIELD_NAME ); ?>"
				maxlength="44"
				inputmode="numeric"
				pattern="\d{44}"
				placeholder="00000000000000000000000000000000000000000000"
				value="<?php echo esc_attr( $value ); ?>"
			/>
			<span class="description">
				<?php esc_html_e( 'Usada pela Melhor Envio ao importar o pedido.', 'melhor-envio-cotacao' ); ?>
			</span>
		</p>
		<?php
	}

	public function save( int $orderId ): void {
		if ( ! isset( $_POST[ self::NONCE_FIELD ] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION )
		) {
			return;
		}

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			return;
		}

		$order = wc_get_order( $orderId );

		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$invoiceKey = isset( $_POST[ self::FIELD_NAME ] )
			? preg_replace( '/\D/', '', sanitize_text_field( wp_unslash( $_POST[ self::FIELD_NAME ] ) ) )
			: '';

		$order->update_meta_data( self::META_KEY, $invoiceKey );
		$order->save();
	}
}
