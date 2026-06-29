<?php

namespace MelhorEnvio\Services;

class InvoiceService {

	const POST_META_INVOICE = 'melhorenvio_invoice_v2';

	/**
	 * Function to search invoice for an order
	 *
	 * @param int $postId
	 * @return array $invoice
	 */
	public function getInvoice( $postId ) {
		$invoice = get_post_meta( $postId, self::POST_META_INVOICE );

		if ( count( $invoice ) > 0 ) {
			return end( $invoice );
		}

		return array(
			'number' => null,
			'key'    => null,
		);
	}
}
