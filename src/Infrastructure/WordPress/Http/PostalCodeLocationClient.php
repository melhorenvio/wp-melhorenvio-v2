<?php

declare(strict_types=1);

namespace MelhorEnvio\Infrastructure\WordPress\Http;

/**
 * Resolves the Brazilian state (UF) for a postal code, so a shipping $package
 * built from just a CEP (e.g. the product-page calculator) can be matched
 * against WooCommerce shipping zones scoped by state. Tries the Melhor Envio
 * location API first, falling back to ViaCEP — same sources the legacy
 * plugin's LocationService used.
 */
final class PostalCodeLocationClient {

	private const LOG_CONTEXT = array( 'source' => 'melhor-envio-cotacao' );

	private function logger(): \WC_Logger {
		return wc_get_logger();
	}

	public function getState( string $cep ): ?string {
		$cep = (string) preg_replace( '/\D/', '', $cep );

		if ( strlen( $cep ) !== 8 ) {
			return null;
		}

		$state = $this->fetchState( "https://location.melhorenvio.com/{$cep}", 'message' );

		if ( ! empty( $state ) ) {
			return $state;
		}

		return $this->fetchState( "https://viacep.com.br/ws/{$cep}/json", 'erro' );
	}

	/**
	 * @param string $errorField Response field that signals a "not found" error for this source.
	 */
	private function fetchState( string $url, string $errorField ): ?string {
		$response = wp_remote_get( $url, array( 'timeout' => 5 ) );

		if ( is_wp_error( $response ) ) {
			$this->logger()->warning(
				sprintf( 'Falha ao consultar %s: %s', $url, $response->get_error_message() ),
				self::LOG_CONTEXT
			);
			return null;
		}

		if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) || ! empty( $body[ $errorField ] ) || empty( $body['uf'] ) ) {
			return null;
		}

		return (string) $body['uf'];
	}
}
