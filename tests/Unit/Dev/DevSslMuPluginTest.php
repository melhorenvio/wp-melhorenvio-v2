<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Dev;

use MelhorEnvio\Tests\TestCase;

final class DevSslMuPluginTest extends TestCase {

	private const MU_PLUGIN = __DIR__ . '/../../../docker/mu-plugins/melhor-envio-dev-ssl.php';

	protected function setUp(): void {
		parent::setUp();
		require self::MU_PLUGIN;
	}

	public function test_registers_both_ssl_filters_with_url_argument(): void {
		self::assertSame( 10, has_filter( 'https_ssl_verify', 'melhor_envio_dev_ssl_verify' ) );
		self::assertSame( 10, has_filter( 'https_local_ssl_verify', 'melhor_envio_dev_ssl_verify' ) );
	}

	/**
	 * @dataProvider localUrls
	 */
	public function test_disables_verification_for_local_hosts( string $url ): void {
		self::assertFalse( melhor_envio_dev_ssl_verify( true, $url ) );
	}

	public function localUrls(): array {
		return array(
			'localhost'             => array( 'https://localhost/wp-json' ),
			'subdominio .localhost' => array( 'https://app.localhost/api' ),
			'subdominio .me.test'   => array( 'https://app.me.test/webhooks/wordpress' ),
			'maiusculas'            => array( 'https://COTACAO.LOCALHOST/wp-cron.php' ),
		);
	}

	/**
	 * @dataProvider externalUrls
	 */
	public function test_keeps_verification_for_other_hosts( string $url ): void {
		self::assertTrue( melhor_envio_dev_ssl_verify( true, $url ) );
		self::assertSame( '/etc/ssl/ca.pem', melhor_envio_dev_ssl_verify( '/etc/ssl/ca.pem', $url ) );
	}

	public function externalUrls(): array {
		return array(
			'api do Melhor Envio'  => array( 'https://melhorenvio.com.br/api/v2/me/shipment/calculate' ),
			'updates do WordPress' => array( 'https://api.wordpress.org/core/version-check/1.7/' ),
			'sufixo sem ponto'     => array( 'https://evil-localhost.com/' ),
			'localhost no caminho' => array( 'https://example.com/app.localhost' ),
			'sem url'              => array( '' ),
		);
	}
}
