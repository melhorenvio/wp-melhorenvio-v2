<?php

declare(strict_types=1);

namespace MelhorEnvio\Services\Auth;

final class SignatureService {

	private const TRANSIENT_KEY = 'melhor_envio_integrador_signature';
	private const ISSUED_AT_KEY = 'melhor_envio_integrador_signature_issued_at';
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
		$stored    = get_transient( self::TRANSIENT_KEY );
		$signature = is_string( $stored ) && $stored !== '' ? $stored : null;
		$issuedAt  = (int) get_transient( self::ISSUED_AT_KEY );

		// Sem data de geração (gravada pela 3.0.7 ou anterior): ainda vale no /secret, mas o admin recebe uma nova.
		if ( $signature !== null && $issuedAt === 0 ) {
			return $generate ? $this->generateSignature() : $signature;
		}

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

	/**
	 * A assinatura continua gravada como string, no mesmo formato da 3.0.7, e a data de
	 * geração fica num transient à parte: assim um rollback do plugin segue lendo o valor.
	 */
	private function saveSignature( string $signature, int $issuedAt ): bool {
		set_transient( self::ISSUED_AT_KEY, $issuedAt, self::TTL );

		return set_transient( self::TRANSIENT_KEY, $signature, self::TTL );
	}

	public function deleteSignature(): bool {
		delete_transient( self::ISSUED_AT_KEY );

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
