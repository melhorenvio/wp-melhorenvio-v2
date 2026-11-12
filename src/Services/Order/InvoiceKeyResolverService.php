<?php

declare(strict_types=1);

namespace MelhorEnvio\Services\Order;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class InvoiceKeyResolverService {

	public const FILTER = 'melhorenvio_invoice_key';

	private const PROVIDER_META = 'nfe';

	private const NFE_KEY_PATTERN = '/^\d{20}55\d{22}$/D';

	public function resolve( \WC_Order $order ): ?string {
		$key = null;

		foreach ( $this->nfeRecords( $order ) as $record ) {
			$recordKey = $this->normalizedKey( $record );

			if ( $recordKey !== null && ( $record['status'] ?? null ) === 'aprovado' ) {
				$key = $recordKey;
			}
		}

		$filtered = apply_filters( self::FILTER, $key, $order );

		return is_string( $filtered ) && preg_match( self::NFE_KEY_PATTERN, $filtered ) ? $filtered : null;
	}

	public function providerKeys( \WC_Order $order ): array {
		$keys = array();

		foreach ( $this->nfeRecords( $order ) as $record ) {
			$key = $this->normalizedKey( $record );

			if ( $key !== null ) {
				$keys[] = $key;
			}
		}

		return $keys;
	}

	private function nfeRecords( \WC_Order $order ): array {
		$records = $order->get_meta( self::PROVIDER_META, true );

		if ( ! is_array( $records ) ) {
			return array();
		}

		return array_filter(
			$records,
			static function ( $record ): bool {
				return is_array( $record ) && ( $record['modelo'] ?? 'nfe' ) === 'nfe';
			}
		);
	}

	private function normalizedKey( array $record ): ?string {
		$key = $record['chave_acesso'] ?? null;

		if ( ! is_string( $key ) ) {
			return null;
		}

		$digits = preg_replace( '/\D/', '', $key );

		return preg_match( self::NFE_KEY_PATTERN, $digits ) ? $digits : null;
	}
}
