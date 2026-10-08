<?php

declare(strict_types=1);

namespace MelhorEnvio\Http\Controllers\Order;

use MelhorEnvio\Http\Controllers\Contracts\ControllerInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extracts the NF-e access key from order notes written by ERPs (e.g. Bling)
 * that don't store invoice data as order meta.
 */
final class OrderNoteInvoiceKeyController implements ControllerInterface {

	// 20 digits (UF, AAMM, CNPJ) + model 55 (positions 21-22) + 22 digits.
	private const NFE_KEY_PATTERN = '/\b\d{20}55\d{22}\b/';

	public function register(): void {
		add_action( 'woocommerce_order_note_added', array( $this, 'extractInvoiceKey' ), 10, 2 );
	}

	public function extractInvoiceKey( $noteId, $order ): void {
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$note = get_comment( (int) $noteId );

		if ( ! $note instanceof \WP_Comment ) {
			return;
		}

		if ( ! preg_match( self::NFE_KEY_PATTERN, (string) $note->comment_content, $matches ) ) {
			return;
		}

		$invoiceKey = $matches[0];

		if ( $order->get_meta( OrderInvoiceKeyMetaBoxController::META_KEY, true ) === $invoiceKey ) {
			return;
		}

		$order->update_meta_data( OrderInvoiceKeyMetaBoxController::META_KEY, $invoiceKey );
		$order->save();
	}
}
