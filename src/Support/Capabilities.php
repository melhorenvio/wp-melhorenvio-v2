<?php

declare(strict_types=1);

namespace MelhorEnvio\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Capacidades exigidas no admin do plugin.
 */
final class Capabilities {

	// Filtro para o lojista ajustar quem acessa a página e as ações de uso diário.
	public const ADMIN_FILTER = 'melhorenvio_admin_capability';

	// Mesma capacidade do plugin legado: libera o Gerente de loja (shop_manager).
	public const ADMIN_DEFAULT = 'manage_woocommerce';

	// Ações sensíveis da conta (conectar, reconectar, trocar credenciais) seguem restritas ao administrador.
	public const ACCOUNT = 'manage_options';

	public static function admin(): string {
		$capability = apply_filters( self::ADMIN_FILTER, self::ADMIN_DEFAULT );

		return is_string( $capability ) && $capability !== '' ? $capability : self::ADMIN_DEFAULT;
	}

	public static function canManageAccount(): bool {
		return current_user_can( self::ACCOUNT );
	}

	// Usar a conta conectada exige ao menos a capacidade padrão, mesmo que o filtro libere a página para menos.
	public static function canUseConnection(): bool {
		return current_user_can( self::ADMIN_DEFAULT ) || self::canManageAccount();
	}
}
