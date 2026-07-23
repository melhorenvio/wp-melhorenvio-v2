<?php

declare(strict_types=1);

namespace MelhorEnvio\Infrastructure\WordPress\Admin;

final class PluginModeManager {

	private const OPTION_KEY = 'melhor_envio_ui_mode';

	public static function getMode(): string {
		return get_option( self::OPTION_KEY, 'legacy' );
	}

	public static function isIntegradorMode(): bool {
		return self::getMode() === 'integrador';
	}

	public static function setMode( string $mode ): void {
		update_option( self::OPTION_KEY, $mode );
	}
}
