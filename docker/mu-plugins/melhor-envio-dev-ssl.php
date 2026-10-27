<?php
/**
 * Plugin Name: Melhor Envio — SSL do ambiente local
 * Description: Somente dev. Aceita o certificado interno do Caddy nos hosts locais (*.localhost, *.me.test).
 *
 * Montado em wp-content/mu-plugins pelo compose.yml; fica fora do pacote do plugin
 * (.distignore e build-dev-zip.yml). Nunca desliga a verificação para outros hosts.
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'melhor_envio_dev_ssl_verify' ) ) {
	/**
	 * @param bool|string $verify Valor atual da verificação (bool ou caminho do bundle de CAs).
	 * @param string      $url    URL da requisição.
	 * @return bool|string
	 */
	function melhor_envio_dev_ssl_verify( $verify, $url = '' ) {
		$host = strtolower( (string) parse_url( (string) $url, PHP_URL_HOST ) );

		foreach ( array( '.localhost', '.me.test' ) as $suffix ) {
			if ( 'localhost' === $host || substr( $host, -strlen( $suffix ) ) === $suffix ) {
				return false;
			}
		}

		return $verify;
	}
}

add_filter( 'https_ssl_verify', 'melhor_envio_dev_ssl_verify', 10, 2 );
add_filter( 'https_local_ssl_verify', 'melhor_envio_dev_ssl_verify', 10, 2 );
