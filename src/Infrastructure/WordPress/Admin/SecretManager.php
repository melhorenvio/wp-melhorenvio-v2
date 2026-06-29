<?php

declare(strict_types=1);

namespace MelhorEnvio\Infrastructure\WordPress\Admin;

final class SecretManager {

	private const OPTION_NAME = 'melhor_envio_integrador_secret';

	public function getSecret(): ?string {
		$secret = get_option( self::OPTION_NAME );
		return $secret ?: null;
	}

	public function setSecret( string $secret ): bool {
		return update_option( self::OPTION_NAME, $secret );
	}

	public function deleteSecret(): bool {
		return delete_option( self::OPTION_NAME );
	}
}
