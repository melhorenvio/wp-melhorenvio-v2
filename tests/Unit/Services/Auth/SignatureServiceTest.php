<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Services\Auth;

use Brain\Monkey\Functions;
use MelhorEnvio\Services\Auth\SignatureService;
use MelhorEnvio\Tests\TestCase;
use Mockery;

final class SignatureServiceTest extends TestCase {

	private const TRANSIENT_KEY = 'melhor_envio_integrador_signature';

	private SignatureService $service;

	protected function setUp(): void {
		parent::setUp();
		$this->service = new SignatureService();
	}

	public function test_generate_signature_stores_hmac_as_transient(): void {
		Functions\when( 'wp_create_nonce' )->justReturn( 'nonce' );
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'get_option' )->justReturn( 'secret' );

		Functions\expect( 'set_transient' )
			->once()
			->with( self::TRANSIENT_KEY, Mockery::pattern( '/^[a-f0-9]{64}$/' ), DAY_IN_SECONDS )
			->andReturn( true );

		$signature = $this->service->generateSignature();

		self::assertMatchesRegularExpression( '/^[a-f0-9]{64}$/', $signature );
	}

	public function test_generate_signature_creates_secret_when_missing(): void {
		Functions\when( 'wp_create_nonce' )->justReturn( 'nonce' );
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'set_transient' )->justReturn( true );
		Functions\when( 'get_option' )->justReturn( false );
		Functions\when( 'wp_generate_password' )->justReturn( 'generated' );

		Functions\expect( 'update_option' )->once()->with( 'melhor_envio_integrador_signature_key', 'generated' );

		$this->service->generateSignature();
	}

	public function test_get_signature_returns_null_when_missing_and_not_generating(): void {
		Functions\when( 'get_transient' )->justReturn( false );

		self::assertNull( $this->service->getSignature( false ) );
	}

	public function test_validate_signature_consumes_matching_signature(): void {
		Functions\when( 'get_transient' )->justReturn( 'abc' );
		Functions\expect( 'delete_transient' )->once()->with( self::TRANSIENT_KEY )->andReturn( true );

		self::assertTrue( $this->service->validateSignature( 'abc' ) );
	}

	public function test_validate_signature_rejects_mismatch(): void {
		Functions\when( 'get_transient' )->justReturn( 'abc' );
		Functions\expect( 'delete_transient' )->never();

		self::assertFalse( $this->service->validateSignature( 'xyz' ) );
	}

	public function test_validate_signature_rejects_when_nothing_stored(): void {
		Functions\when( 'get_transient' )->justReturn( false );

		self::assertFalse( $this->service->validateSignature( 'abc' ) );
	}
}
