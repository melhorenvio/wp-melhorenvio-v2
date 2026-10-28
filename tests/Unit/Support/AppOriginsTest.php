<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Support;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use MelhorEnvio\Support\AppOrigins;
use MelhorEnvio\Tests\TestCase;

final class AppOriginsTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'wp_parse_url' )->alias( 'parse_url' );
	}

	/**
	 * @dataProvider backendUrls
	 */
	public function test_allows_registrable_domain_of_backend( string $baseUrl, string $domain ): void {
		self::assertSame( array( $domain ), AppOrigins::allowedDomains( $baseUrl ) );
	}

	public function backendUrls(): array {
		return array(
			'produção'           => array( 'https://woocommerceenvios.com', 'woocommerceenvios.com' ),
			'dev (app irmão)'    => array( 'https://wordpress-envios.melhorenvio.work', 'melhorenvio.work' ),
			'sufixo de 2 níveis' => array( 'https://api.melhorenvio.com.br/', 'melhorenvio.com.br' ),
			'host maiúsculo'     => array( 'https://WooCommerceEnvios.com', 'woocommerceenvios.com' ),
			'localhost'          => array( 'http://localhost:8080', 'localhost' ),
			'ip'                 => array( 'http://127.0.0.1:8000', '127.0.0.1' ),
		);
	}

	public function test_filter_replaces_domains_and_accepts_origins(): void {
		Filters\expectApplied( AppOrigins::FILTER )
			->once()
			->with( array( 'woocommerceenvios.com' ) )
			->andReturn( array( 'woocommerceenvios.com', 'https://App.Exemplo.com.br/' ) );

		self::assertSame(
			array( 'woocommerceenvios.com', 'app.exemplo.com.br' ),
			AppOrigins::allowedDomains( 'https://woocommerceenvios.com' )
		);
	}

	/**
	 * @dataProvider invalidFilteredValues
	 * @param mixed $filtered
	 */
	public function test_falls_back_to_default_when_filter_returns_invalid_value( $filtered ): void {
		Filters\expectApplied( AppOrigins::FILTER )->once()->andReturn( $filtered );

		self::assertSame( array( 'woocommerceenvios.com' ), AppOrigins::allowedDomains( 'https://woocommerceenvios.com' ) );
	}

	public function invalidFilteredValues(): array {
		return array(
			'string'        => array( 'evil.example' ),
			'null'          => array( null ),
			'lista vazia'   => array( array() ),
			'só inválidos'  => array( array( '', 'not a host', 42, 'https://' ) ),
		);
	}

	public function test_drops_invalid_entries_and_keeps_valid_ones(): void {
		Filters\expectApplied( AppOrigins::FILTER )->once()->andReturn( array( 'not a host', 'app.exemplo.com' ) );

		self::assertSame( array( 'app.exemplo.com' ), AppOrigins::allowedDomains( 'https://woocommerceenvios.com' ) );
	}

	public function test_returns_empty_list_without_backend_host(): void {
		self::assertSame( array(), AppOrigins::allowedDomains( '' ) );
	}
}
