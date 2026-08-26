<?php

declare(strict_types=1);

namespace MelhorEnvio\Services\Quotation;

final class MelhorEnvioApiClientService {

	private const TOKEN_OPTION   = 'melhor_envio_integrador_quotation_token';
	private const API_URL_OPTION = 'melhor_envio_integrador_me_api_url';

	private function getBaseUrl(): string {
		return rtrim( (string) get_option( self::API_URL_OPTION, 'https://melhorenvio.com.br' ), '/' );
	}

	private function getToken(): ?string {
		$token = get_option( self::TOKEN_OPTION );
		return $token ?: null;
	}

	/**
	 * @param array $items Each: ['weight'=>float, 'width'=>float, 'height'=>float, 'length'=>float, 'quantity'=>int, 'insurance_value'=>float]
	 */
	private function logger(): \WC_Logger {
		return wc_get_logger();
	}

	private const LOG_CONTEXT = array( 'source' => 'melhor-envio-cotacao' );

	public function getQuotations( string $fromCep, string $toCep, array $items ): array {
		$token = $this->getToken();

		if ( empty( $token ) ) {
			$this->logger()->warning( 'Cotação abortada: token de cotação não configurado.', self::LOG_CONTEXT );
			return array();
		}

		$fromCep = preg_replace( '/\D/', '', $fromCep );
		$toCep   = preg_replace( '/\D/', '', $toCep );

		if ( strlen( $fromCep ) !== 8 || strlen( $toCep ) !== 8 ) {
			$this->logger()->warning(
				sprintf( 'Cotação abortada: CEP inválido. from=%s to=%s', $fromCep, $toCep ),
				self::LOG_CONTEXT
			);
			return array();
		}

		$payload = array(
			'from'     => array( 'postal_code' => $fromCep ),
			'to'       => array( 'postal_code' => $toCep ),
			'products' => $items,
		);

		$this->logger()->debug(
			sprintf( 'Cotação payload enviado. from=%s to=%s payload=%s', $fromCep, $toCep, wp_json_encode( $payload ) ),
			self::LOG_CONTEXT
		);

		$response = wp_remote_post(
			$this->getBaseUrl() . '/api/v2/me/shipment/calculate',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $token,
					'Content-Type'  => 'application/json',
					'Accept'        => 'application/json',
				),
				'body'      => wp_json_encode( $payload ),
				'timeout'   => 15,
				'sslverify' => false,
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->logger()->error(
				sprintf( 'Erro na requisição de cotação: %s', $response->get_error_message() ),
				self::LOG_CONTEXT
			);
			return array();
		}

		$statusCode = wp_remote_retrieve_response_code( $response );

		if ( $statusCode !== 200 ) {
			$this->logger()->error(
				sprintf( 'Cotação retornou HTTP %d. Body: %s', $statusCode, wp_remote_retrieve_body( $response ) ),
				self::LOG_CONTEXT
			);
			return array();
		}

		$rawBody = wp_remote_retrieve_body( $response );

		$this->logger()->debug(
			sprintf( 'Cotação response recebido. HTTP=%d body=%s', $statusCode, $rawBody ),
			self::LOG_CONTEXT
		);

		$body = json_decode( $rawBody, true );

		if ( ! is_array( $body ) ) {
			$this->logger()->error( 'Cotação retornou JSON inválido.', self::LOG_CONTEXT );
			return array();
		}

		$quotations = array_filter(
			$body,
			static fn( $item ) => is_array( $item ) && empty( $item['error'] ) && isset( $item['price'] )
		);

		$this->logger()->debug(
			sprintf( 'Cotação concluída. from=%s to=%s serviços=%d', $fromCep, $toCep, count( $quotations ) ),
			self::LOG_CONTEXT
		);

		return $quotations;
	}
}
