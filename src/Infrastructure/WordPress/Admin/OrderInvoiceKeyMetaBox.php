<?php

declare(strict_types=1);

namespace MelhorEnvio\Infrastructure\WordPress\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class OrderInvoiceKeyMetaBox {

	public const META_KEY = '_me_invoice_key';

	private const NONCE_ACTION        = 'me_save_invoice_key';
	private const NONCE_FIELD         = 'me_invoice_key_nonce';
	private const FIELD_NAME          = 'me_invoice_key';
	private const UPLOAD_NONCE_ACTION = 'upload_nfe_xml';

	public function register(): void {
		add_action( 'add_meta_boxes', array( $this, 'addMetaBox' ) );
		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'save' ) );
	}

	public function addMetaBox(): void {
		$screens = array( 'shop_order' );
		if ( function_exists( 'wc_get_page_screen_id' ) ) {
			$screens[] = wc_get_page_screen_id( 'shop-order' );
		}
		foreach ( array_unique( $screens ) as $screen ) {
			add_meta_box(
				'me_invoice_data',
				__( 'Nota Fiscal', 'melhor-envio-cotacao' ),
				array( $this, 'render' ),
				$screen,
				'normal',
				'default'
			);
		}
	}

	public function render( $postOrOrder ): void {
		$order = $postOrOrder instanceof \WC_Order
			? $postOrOrder
			: wc_get_order( $postOrOrder->ID );

		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		$invoiceKey = $order->get_meta( self::META_KEY, true );
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
				value="<?php echo esc_attr( $invoiceKey ); ?>"
			/>
			<span class="description">
				<?php esc_html_e( 'Usada pela Melhor Envio ao importar o pedido.', 'melhor-envio-cotacao' ); ?>
			</span>
		</p>
		<p class="form-field">
			<label><?php esc_html_e( 'Importar XML NF-e', 'melhor-envio-cotacao' ); ?></label>
			<input type="file" id="me_nfe_xml_upload" accept=".xml" />
			<button type="button" id="me_nfe_xml_btn" class="button">
				<?php esc_html_e( 'Importar', 'melhor-envio-cotacao' ); ?>
			</button>
			<span id="me_nfe_xml_status" style="margin-left:8px;"></span>
			<input type="hidden" id="me_nfe_upload_nonce" value="<?php echo esc_attr( wp_create_nonce( self::UPLOAD_NONCE_ACTION ) ); ?>" />
			<input type="hidden" id="me_nfe_order_id" value="<?php echo esc_attr( (string) $order->get_id() ); ?>" />
		</p>
		<script>
		(function () {
			document.getElementById('me_nfe_xml_btn').addEventListener('click', function () {
				var fileInput = document.getElementById('me_nfe_xml_upload');
				var file = fileInput.files[0];
				if (!file) return;

				var status = document.getElementById('me_nfe_xml_status');
				status.textContent = '<?php echo esc_js( __( 'Processando…', 'melhor-envio-cotacao' ) ); ?>';

				var fd = new FormData();
				fd.append('action', '<?php echo esc_js( self::UPLOAD_NONCE_ACTION ); ?>');
				fd.append('order_id', document.getElementById('me_nfe_order_id').value);
				fd.append('nfe_xml', file);
				fd.append('nonce', document.getElementById('me_nfe_upload_nonce').value);

				fetch(ajaxurl, { method: 'POST', body: fd })
					.then(function (r) { return r.json(); })
					.then(function (data) {
						if (data.success) {
							document.getElementById('<?php echo esc_js( self::FIELD_NAME ); ?>').value = data.data.key;
							status.textContent = '<?php echo esc_js( __( 'Importado com sucesso.', 'melhor-envio-cotacao' ) ); ?>';
						} else {
							status.textContent = (data.data && data.data.message) || '<?php echo esc_js( __( 'Erro ao processar XML.', 'melhor-envio-cotacao' ) ); ?>';
						}
					})
					.catch(function () {
						status.textContent = '<?php echo esc_js( __( 'Erro na requisição.', 'melhor-envio-cotacao' ) ); ?>';
					});
			});
		})();
		</script>
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
