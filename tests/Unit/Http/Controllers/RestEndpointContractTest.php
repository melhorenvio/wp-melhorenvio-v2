<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Http\Controllers;

use Brain\Monkey\Actions;
use MelhorEnvio\Tests\TestCase;
use MelhorEnvio\Tests\Unit\Http\Controllers\Fixtures\FakeRestEndpoint;

require_once __DIR__ . '/Fixtures/ContractFixtures.php';

final class RestEndpointContractTest extends TestCase {

	public function test_register_defers_route_registration_to_rest_api_init(): void {
		$endpoint = $this->makeEndpoint();

		$endpoint->register();

		self::assertSame( 10, has_action( 'rest_api_init', array( $endpoint, 'registerRoute' ) ) );
		self::assertSame( 0, $endpoint->routesRegistered );
	}

	public function test_rest_api_init_callback_registers_route(): void {
		$endpoint = $this->makeEndpoint();
		$callback = null;

		Actions\expectAdded( 'rest_api_init' )->once()->whenHappen(
			static function ( $cb ) use ( &$callback ): void {
				$callback = $cb;
			}
		);

		$endpoint->register();
		call_user_func( $callback );

		self::assertSame( 1, $endpoint->routesRegistered );
	}

	public function test_exposes_plugin_api_namespace(): void {
		self::assertSame( 'wp-melhor-integrador/v1', $this->makeEndpoint()->apiNamespace() );
	}

	private function makeEndpoint(): FakeRestEndpoint {
		return new FakeRestEndpoint();
	}
}
