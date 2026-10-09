<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Services\Auth;

use Brain\Monkey\Functions;
use MelhorEnvio\Services\Auth\SecretService;
use MelhorEnvio\Tests\TestCase;

final class SecretServiceTest extends TestCase {

	private const OPTION_NAME = 'melhor_envio_integrador_secret';

	private SecretService $service;

	protected function setUp(): void {
		parent::setUp();
		$this->service = new SecretService();
	}

	public function test_get_secret_returns_stored_value(): void {
		Functions\expect( 'get_option' )->once()->with( self::OPTION_NAME )->andReturn( 's3cr3t' );

		self::assertSame( 's3cr3t', $this->service->getSecret() );
	}

	/**
	 * @dataProvider emptyOptionValues
	 *
	 * @param mixed $stored
	 */
	public function test_get_secret_returns_null_when_option_is_empty( $stored ): void {
		Functions\when( 'get_option' )->justReturn( $stored );

		self::assertNull( $this->service->getSecret() );
	}

	public function emptyOptionValues(): array {
		return array(
			'missing option' => array( false ),
			'empty string'   => array( '' ),
		);
	}

	public function test_set_secret_persists_option_and_returns_result(): void {
		Functions\when( 'get_option' )->justReturn( 'old-secret' );
		Functions\expect( 'update_option' )->once()->with( self::OPTION_NAME, 'new-secret' )->andReturn( true );

		self::assertTrue( $this->service->setSecret( 'new-secret' ) );
	}

	public function test_set_secret_returns_false_when_update_fails(): void {
		Functions\when( 'get_option' )->justReturn( 'old-secret' );
		Functions\when( 'update_option' )->justReturn( false );

		self::assertFalse( $this->service->setSecret( 'new-secret' ) );
	}

	public function test_set_secret_returns_true_without_writing_when_value_is_unchanged(): void {
		Functions\when( 'get_option' )->justReturn( 'same-secret' );
		Functions\expect( 'update_option' )->never();

		self::assertTrue( $this->service->setSecret( 'same-secret' ) );
	}

	public function test_delete_secret_removes_option(): void {
		Functions\expect( 'delete_option' )->once()->with( self::OPTION_NAME )->andReturn( true );

		self::assertTrue( $this->service->deleteSecret() );
	}

	public function test_delete_secret_returns_false_when_nothing_deleted(): void {
		Functions\when( 'delete_option' )->justReturn( false );

		self::assertFalse( $this->service->deleteSecret() );
	}
}
