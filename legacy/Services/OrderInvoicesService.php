<?php

namespace MelhorEnvio\Services;

class OrderInvoicesService {

	const POST_META_INVOICE = 'melhorenvio_invoice_v2';

	/**
	 * Function to save invoice by order.
	 *
	 * @param int     $postId
	 * @param numeric $key
	 * @param numeric $number
	 * @return array
	 */
	public function insertInvoiceOrder( $postId, $key, $number ) {
		delete_post_meta( $postId, self::POST_META_INVOICE );

		$invoice = array(
			'key'    => $key,
			'number' => $number,
		);

		$result = add_post_meta(
			$postId,
			self::POST_META_INVOICE,
			$invoice,
			true
		);

		if ( ! $result ) {
			return array(
				'key'    => null,
				'number' => null,
			);
		}

		return $invoice;
	}

	/**
	 * Function to retrieve invoice by order.
	 *
	 * @param int $postId
	 * @return array $invoice
	 */
	public function getInvoiceOrder( $postId ) {
		$invoice = get_post_meta( $postId, self::POST_META_INVOICE, true );

		if ( ! $invoice ) {
			return array(
				'key'    => null,
				'number' => null,
			);
		}

		return $invoice;
	}

	/**
	 * Function to check order is non commercial
	 *
	 * @param int $postId
	 * @return boolean
	 */
	public function isNonCommercial( $postId ) {
		$invoice = get_post_meta( $postId, self::POST_META_INVOICE, true );

		return ( ! isset( $invoice['key'] ) || ! isset( $invoice['number'] ) );
	}
}
