<?php

namespace MelhorEnvio\Helpers;

class TranslateStatusHelper {

	/**
	 * Function to translate Stauts.
	 *
	 * @param string $status
	 * @return string $status
	 */
	public function translateNameStatus( $status = null ) {
		$statusTranslate = '';
		if ( $status == 'pending' ) {
			$statusTranslate = 'Pendente';
		} elseif ( $status == 'released' ) {
			$statusTranslate = 'Liberado';
		} elseif ( $status == 'posted' ) {
			$statusTranslate = 'Postado';
		} elseif ( $status == 'delivered' ) {
			$statusTranslate = 'Entregue';
		} elseif ( $status == 'canceled' ) {
			$statusTranslate = 'Cancelado';
		} elseif ( $status == 'undelivered' ) {
			$statusTranslate = 'Não entregue';
		} elseif ( $status == 'generated' ) {
			$statusTranslate = 'Gerada';
		} elseif ( $status == 'paid' ) {
			$statusTranslate = 'Paga';
		} else {
			$statusTranslate = 'Não possui';
		}

		return $statusTranslate;
	}
}
