<?php

declare(strict_types=1);

namespace MelhorEnvio\Http\Controllers\Order;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class OrderInvoiceKeyMetaBoxController {

	public const META_KEY = '_me_invoice_key';

	private const NONCE_ACTION   = 'me_save_invoice_key';
	private const NONCE_FIELD    = 'me_invoice_key_nonce';
	private const FIELD_NAME     = 'me_invoice_key';
	private const XML_FIELD_NAME = 'me_nfe_raw_xml';

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
		$hasXml     = ! empty( $order->get_meta( '_me_invoice_xml_danfe', true ) );
		?>
		<style>
		#me-nfe-box {
			display: flex;
			gap: 16px;
			align-items: flex-start;
		}
		#me-nfe-upload-col {
			flex: 1;
		}
		#me-nfe-drop-zone {
			position: relative;
			border: 2px dashed #c3c4c7;
			border-radius: 4px;
			padding: 24px 16px;
			text-align: center;
			cursor: pointer;
			transition: border-color .15s, background .15s;
			background: #fafafa;
		}
		#me-nfe-drop-zone:hover,
		#me-nfe-drop-zone.me-drag-over {
			border-color: #2271b1;
			background: #f0f6fc;
		}
		#me-nfe-drop-zone input[type="file"] {
			position: absolute;
			inset: 0;
			opacity: 0;
			cursor: pointer;
			width: 100%;
			height: 100%;
		}
		#me-nfe-key-col {
			min-width: 0;
			align-self: center;
		}
		.me-nfe-key-wrap input {
			font-family: monospace;
			letter-spacing: 1px;
		}
		#me-nfe-upload-status {
			margin-top: 6px;
			font-size: 12px;
			min-height: 14px;
		}
		</style>

		<div id="me-nfe-box">

			<!-- Left: XML upload zone -->
			<div id="me-nfe-upload-col">
				<p style="margin:0 0 6px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:#646970;">
					<?php esc_html_e( 'Importar XML NF-e', 'melhor-envio-cotacao' ); ?>
				</p>
				<div id="me-nfe-drop-zone">
					<input type="file" id="me_nfe_xml_upload" accept=".xml" />
					<?php if ( $hasXml ) : ?>
					<span style="font-size:13px;color:#1d7417;font-weight:600;">
						<?php esc_html_e( 'XML importado', 'melhor-envio-cotacao' ); ?>
					</span>
					<span style="display:block;font-size:11px;color:#8c8f94;margin-top:4px;">
						<?php esc_html_e( 'Clique para substituir', 'melhor-envio-cotacao' ); ?>
					</span>
					<?php else : ?>
					<span style="font-size:28px;display:block;margin-bottom:6px;">📄</span>
					<span style="font-size:13px;color:#50575e;">
						<?php esc_html_e( 'Arraste ou clique para selecionar', 'melhor-envio-cotacao' ); ?>
					</span>
					<span style="display:block;font-size:11px;color:#8c8f94;margin-top:4px;">
						<?php esc_html_e( 'Máx. 500 KB · .xml', 'melhor-envio-cotacao' ); ?>
					</span>
					<?php endif; ?>
				</div>
				<div id="me-nfe-upload-status"></div>
			</div>

			<!-- Right: key field -->
			<div id="me-nfe-key-col">
				<label for="<?php echo esc_attr( self::FIELD_NAME ); ?>" style="display:block;font-weight:600;margin-bottom:6px;">
					<?php esc_html_e( 'Chave de acesso (44 dígitos)', 'melhor-envio-cotacao' ); ?>
				</label>
				<div class="me-nfe-key-wrap">
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
				</div>
				<p class="description" style="margin:6px 0 0;">
					<?php esc_html_e( 'Preenchido automaticamente ao importar o XML. Salve o pedido para confirmar.', 'melhor-envio-cotacao' ); ?>
				</p>
			</div>

		</div>

		<!-- Hidden field: raw XML content, filled by JS on file select -->
		<textarea
			name="<?php echo esc_attr( self::XML_FIELD_NAME ); ?>"
			id="me_nfe_raw_xml"
			style="display:none;"
		></textarea>

		<script>
		(function () {
			var keyField     = document.getElementById('<?php echo esc_js( self::FIELD_NAME ); ?>');
			var rawXmlField  = document.getElementById('me_nfe_raw_xml');
			var uploadStatus = document.getElementById('me-nfe-upload-status');
			var dropZone     = document.getElementById('me-nfe-drop-zone');
			var fileInput    = document.getElementById('me_nfe_xml_upload');

			function getTag(doc, tag) {
				var els = doc.getElementsByTagName(tag);
				return els.length ? els[0].textContent.trim() : '';
			}

			function parseNFeXml(text) {
				var parser = new DOMParser();
				var doc = parser.parseFromString(text, 'text/xml');
				if (doc.querySelector('parseerror, parsererror')) return null;

				var chNFe = getTag(doc, 'chNFe');
				if (!chNFe) {
					var infNFe = doc.getElementsByTagName('infNFe')[0];
					if (infNFe) chNFe = (infNFe.getAttribute('Id') || '').replace(/^NFe/, '');
				}

				return { chave: chNFe };
			}

			function setStatus(msg, color) {
				uploadStatus.style.color = color || '#646970';
				uploadStatus.textContent = msg;
			}

			function showFilePreview(file) {
				var kb = (file.size / 1024).toFixed(1);
				dropZone.innerHTML =
					'<input type="file" id="me_nfe_xml_upload" accept=".xml" />'
					+ '<span style="font-size:13px;color:#1d7417;font-weight:600;word-break:break-all;">' + file.name + '</span>'
					+ '<span style="display:block;font-size:11px;color:#8c8f94;margin-top:4px;">' + kb + ' KB · clique para trocar</span>';
				dropZone.querySelector('input[type="file"]').addEventListener('change', function () {
					processFile(this.files[0]);
				});
			}

			function processFile(file) {
				if (!file) return;

				if (file.size > 512000) {
					setStatus('<?php echo esc_js( __( 'Arquivo excede 500 KB.', 'melhor-envio-cotacao' ) ); ?>', '#d63638');
					return;
				}

				setStatus('<?php echo esc_js( __( 'Lendo XML…', 'melhor-envio-cotacao' ) ); ?>');

				var reader = new FileReader();
				reader.onload = function (e) {
					var text = e.target.result;
					var data = parseNFeXml(text);

					if (!data || !data.chave) {
						setStatus('<?php echo esc_js( __( 'XML inválido ou chave não encontrada.', 'melhor-envio-cotacao' ) ); ?>', '#d63638');
						rawXmlField.value = '';
						return;
					}

					if (!/^\d{44}$/.test(data.chave)) {
						setStatus('<?php echo esc_js( __( 'Chave extraída inválida (deve ter 44 dígitos).', 'melhor-envio-cotacao' ) ); ?>', '#d63638');
						rawXmlField.value = '';
						return;
					}

					keyField.value    = data.chave;
					rawXmlField.value = text;
					showFilePreview(file);
					setStatus('');
				};
				reader.onerror = function () {
					setStatus('<?php echo esc_js( __( 'Erro ao ler o arquivo.', 'melhor-envio-cotacao' ) ); ?>', '#d63638');
				};
				reader.readAsText(file);
			}

			fileInput.addEventListener('change', function () {
				processFile(this.files[0]);
			});

			dropZone.addEventListener('dragover', function (e) {
				e.preventDefault();
				dropZone.classList.add('me-drag-over');
			});
			dropZone.addEventListener('dragleave', function () {
				dropZone.classList.remove('me-drag-over');
			});
			dropZone.addEventListener('drop', function (e) {
				e.preventDefault();
				dropZone.classList.remove('me-drag-over');
				var file = e.dataTransfer.files[0];
				if (file) processFile(file);
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

		if ( ! empty( $_POST[ self::XML_FIELD_NAME ] ) ) {
			$rawXml = wp_unslash( $_POST[ self::XML_FIELD_NAME ] );
			if ( strlen( $rawXml ) <= 512000 ) {
				$order->update_meta_data( '_me_invoice_xml_danfe', $rawXml );
			}
		}

		$order->save();
	}
}
