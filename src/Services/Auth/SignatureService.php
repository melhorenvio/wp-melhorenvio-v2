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

	public function generateSignature(): string {
		$data = array(
			'timestamp' => time(),
			'nonce'     => wp_create_nonce( self::TRANSIENT_KEY ),
		);

		$signature = hash_hmac( 'sha256', serialize( $data ), $this->getSecretKey() );

		$this->saveSignature( $signature );

		return $signature;
	}

	public function getSignature( bool $generate = true ): ?string {
		$signature = get_transient( self::TRANSIENT_KEY );

		if ( ! $signature && $generate ) {
			$signature = $this->generateSignature();
		} elseif ( $signature && $generate ) {
			// Entregue de novo ao admin: renova a validade para o handshake não expirar no meio.
			$this->saveSignature( $signature );
		}

		return $signature ?: null;
	}

	public function validateSignature( string $signature ): bool {
		$storedSignature = $this->getSignature( false );
		$isValid         = $storedSignature !== null && hash_equals( $storedSignature, $signature );

		if ( $isValid ) {
			$this->deleteSignature();
		}

		return $isValid;
	}

	private function saveSignature( string $signature ): bool {
		return set_transient( self::TRANSIENT_KEY, $signature, self::TTL );
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
