<?php

declare(strict_types=1);

namespace MelhorEnvio\Support;

// Domínios em que o app do Melhor Integrador pode estar, para onde a assinatura vai por postMessage.
final class AppOrigins {

	public const FILTER = 'melhor_envio_app_origins';

	// Sufixos de dois níveis comuns nos domínios do Melhor Envio; o resto usa os dois últimos rótulos.
	private const TWO_LEVEL_SUFFIXES = array( 'com.br', 'net.br', 'org.br', 'app.br', 'dev.br', 'co.uk' );

	/**
	 * Domínio registrável da URL do backend (o app do MI fica nele ou num subdomínio dele) mais o que o
	 * filtro acrescentar. Ex.: woocommerceenvios.com → dashboard.woocommerceenvios.com.
	 *
	 * @return string[]
	 */
	public static function allowedDomains( string $baseUrl ): array {
		$host     = strtolower( (string) wp_parse_url( $baseUrl, PHP_URL_HOST ) );
		$defaults = $host !== '' ? array( self::registrableDomain( $host ) ) : array();

		$filtered = apply_filters( self::FILTER, $defaults );
		if ( ! is_array( $filtered ) ) {
			return $defaults;
		}

		$domains = array_values( array_unique( array_filter( array_map( array( self::class, 'normalize' ), $filtered ) ) ) );

		return $domains !== array() ? $domains : $defaults;
	}

	private static function registrableDomain( string $host ): string {
		if ( filter_var( $host, FILTER_VALIDATE_IP ) || strpos( $host, '.' ) === false ) {
			return $host;
		}

		$labels = explode( '.', $host );
		$keep   = in_array( implode( '.', array_slice( $labels, -2 ) ), self::TWO_LEVEL_SUFFIXES, true ) ? 3 : 2;

		return implode( '.', array_slice( $labels, -$keep ) );
	}

	/**
	 * Aceita domínio puro ou origem (https://app.exemplo.com) e devolve só o host; descarta o inválido.
	 *
	 * @param mixed $value
	 */
	private static function normalize( $value ): ?string {
		if ( ! is_string( $value ) || $value === '' ) {
			return null;
		}

		$host = strpos( $value, '://' ) !== false ? (string) wp_parse_url( $value, PHP_URL_HOST ) : $value;
		$host = strtolower( trim( $host, ". \t\n\r" ) );

		return preg_match( '/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)*$/', $host ) ? $host : null;
	}
}
