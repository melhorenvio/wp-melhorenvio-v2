<?php

declare(strict_types=1);

namespace MelhorEnvio\Services\Auth;

final class SignatureService {

	private const TRANSIENT_KEY = 'melhor_envio_integrador_signature';
	private const SIGNATURE_KEY = 'melhor_envio_integrador_signature_key';

	public function generateSignature(): string {
		$data = array(
			'timestamp' => time(),
			'nonce'     => wp_create_nonce( self::TRANSIENT_KEY ),
		);

		$signature = hash_hmac( 'sha256', serialize( $data ), $this->getSecretKey() );

		$this->saveSignature( $signature );

		return $signature;
	}

	private function getExpirationTime(): int {
		$userId = get_current_user_id();
		return (int) apply_filters( 'auth_cookie_expiration', 1 * DAY_IN_SECONDS, $userId, false );
	}

	public function getSignature( bool $generate = true ): ?string {
		$signature = get_transient( self::TRANSIENT_KEY );

		if ( ! $signature && $generate ) {
			$signature = $this->generateSignature();
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
		$expiration = $this->getExpirationTime();
		return set_transient( self::TRANSIENT_KEY, $signature, $expiration );
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
