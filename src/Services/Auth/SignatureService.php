<?php

declare(strict_types=1);

namespace MelhorEnvio\Services\Auth;

final class SignatureService {

	private const TRANSIENT_KEY = 'melhor_envio_integrador_signature';
	private const SIGNATURE_KEY = 'melhor_envio_integrador_signature_key';

	/**
	 * Validade da assinatura, renovada a cada entrega à página do admin. Ela não depende
	 * da sessão de nenhum usuário: é única para o site e de uso único (consumida no /secret).
	 */
	public const TTL = HOUR_IN_SECONDS;

	/**
	 * Vida máxima da assinatura contada da geração. A renovação do TTL nunca passa desse
	 * teto: depois dele a próxima entrega ao admin gera uma assinatura nova.
	 */
	public const MAX_AGE = 6 * HOUR_IN_SECONDS;

	public function generateSignature(): string {
		$data = array(
			'timestamp' => time(),
			'nonce'     => wp_create_nonce( self::TRANSIENT_KEY ),
		);

		$signature = hash_hmac( 'sha256', serialize( $data ), $this->getSecretKey() );

		$this->saveSignature( $signature, $data['timestamp'] );

		return $signature;
	}

	public function getSignature( bool $generate = true ): ?string {
		$stored = get_transient( self::TRANSIENT_KEY );

		// Formato antigo (sem data de geração): ainda vale no /secret, mas o admin recebe uma nova.
		if ( is_string( $stored ) && $stored !== '' ) {
			return $generate ? $this->generateSignature() : $stored;
		}

		$signature = is_array( $stored ) && ! empty( $stored['signature'] ) ? (string) $stored['signature'] : null;
		$issuedAt  = is_array( $stored ) && isset( $stored['issued_at'] ) ? (int) $stored['issued_at'] : 0;

		// O transient já expira dentro do teto; a checagem cobre caches que não respeitam a validade.
		if ( $signature !== null && time() - $issuedAt > self::MAX_AGE ) {
			$signature = null;
		}

		if ( ! $generate ) {
			return $signature;
		}

		// Sem assinatura, ou a renovação passaria do teto de vida: gera outra.
		if ( $signature === null || time() - $issuedAt + self::TTL > self::MAX_AGE ) {
			return $this->generateSignature();
		}

		// Entregue de novo ao admin: renova a validade para o handshake não expirar no meio.
		$this->saveSignature( $signature, $issuedAt );

		return $signature;
	}

	public function validateSignature( string $signature ): bool {
		$storedSignature = $this->getSignature( false );
		$isValid         = $storedSignature !== null && hash_equals( $storedSignature, $signature );

		if ( $isValid ) {
			$this->deleteSignature();
		}

		return $isValid;
	}

	private function saveSignature( string $signature, int $issuedAt ): bool {
		$data = array(
			'signature' => $signature,
			'issued_at' => $issuedAt,
		);

		return set_transient( self::TRANSIENT_KEY, $data, self::TTL );
	}

	public function deleteSignature(): bool {
		return delete_transient( self::TRANSIENT_KEY );
	}

	private function getSecretKey(): string {
		$secret = get_option( self::SIGNATURE_KEY );

		if ( empty( $secret ) ) {
			$secret = wp_generate_password( 64, true, true );
			update_option( self::SIGNATURE_KEY, $secret );
		}

		return $secret;
	}
}
