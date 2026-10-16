<?php

declare(strict_types=1);

namespace MelhorEnvio\Services\Admin;

final class PluginModeService {

	private const OPTION_KEY = 'melhor_envio_ui_mode';

	public static function getMode(): string {
		return get_option( self::OPTION_KEY, 'legacy' );
	}

	public static function isIntegradorMode(): bool {
		return self::getMode() === 'integrador';
	}

	public static function setMode( string $mode ): void {
		update_option( self::OPTION_KEY, $mode );

		self::toggleMelhorEnvioShippingMethod( $mode === 'integrador' );
	}

	/**
	 * Não desregistra a classe do método (ela continua disponível via
	 * woocommerce_shipping_methods) — apenas ativa/desativa instâncias já
	 * configuradas em zonas de frete, para não haver cotação duplicada com
	 * os métodos legados.
	 */
	private static function toggleMelhorEnvioShippingMethod( bool $enabled ): void {
		global $wpdb;

		$wpdb->update(
			$wpdb->prefix . 'woocommerce_shipping_zone_methods',
			array( 'is_enabled' => $enabled ? 1 : 0 ),
			array( 'method_id' => 'melhor_envio' )
		);

		if ( class_exists( 'WC_Cache_Helper' ) ) {
			\WC_Cache_Helper::get_transient_version( 'shipping', true );
		}
	}
}
