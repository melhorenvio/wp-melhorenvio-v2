<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Services\Auth;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use MelhorEnvio\Services\Auth\SignatureService;
use MelhorEnvio\Tests\TestCase;

final class SignatureServiceTest extends TestCase {

	private const TRANSIENT_KEY = 'melhor_envio_integrador_signature';
	private const ISSUED_AT_KEY = 'melhor_envio_integrador_signature_issued_at';

	private SignatureService $service;

	/** @var array<string, mixed> */
	private array $transients = array();

	/** @var array<string, int> */
	private array $ttls = array();

	protected function setUp(): void {
		parent::setUp();
		$this->service = new SignatureService();
		$this->fakeTransientStore();
		Functions\when( 'wp_create_nonce' )->justReturn( 'nonce' );
		Functions\when( 'get_option' )->justReturn( 'secret' );
	}

	public function test_generate_signature_stores_hmac_as_string_transient(): void {
		$signature = $this->service->generateSignature();

		self::assertMatchesRegularExpression( '/^[a-f0-9]{64}$/', $signature );
		self::assertSame( $signature, $this->transients[ self::TRANSIENT_KEY ] );
		self::assertLessThanOrEqual( 5, abs( time() - $this->transients[ self::ISSUED_AT_KEY ] ) );
		self::assertSame( HOUR_IN_SECONDS, $this->ttls[ self::TRANSIENT_KEY ] );
		self::assertSame( HOUR_IN_SECONDS, $this->ttls[ self::ISSUED_AT_KEY ] );
	}

	public function test_stored_signature_keeps_the_format_read_by_previous_versions(): void {
		$signature = $this->service->generateSignature();

		// A 3.0.7 lê o transient como ?string; um rollback não pode encontrar outro formato.
		self::assertIsString( $this->transients[ self::TRANSIENT_KEY ] );
		self::assertSame( $signature, $this->transients[ self::TRANSIENT_KEY ] );
	}

	public function test_generate_signature_creates_secret_when_missing(): void {
		Functions\when( 'get_option' )->justReturn( false );
		Functions\when( 'wp_generate_password' )->justReturn( 'generated' );

		Functions\expect( 'update_option' )->once()->with( 'melhor_envio_integrador_signature_key', 'generated' );

		$this->service->generateSignature();

		$this->assertMockExpectationsMet();
	}

	public function test_ttl_is_short_and_ignores_auth_cookie_expiration(): void {
		$this->service->generateSignature();

		self::assertSame( HOUR_IN_SECONDS, SignatureService::TTL );
		self::assertFalse( Filters\applied( 'auth_cookie_expiration' ) > 0 );
	}

	public function test_max_age_caps_lifetime_at_a_few_hours(): void {
		self::assertGreaterThan( SignatureService::TTL, SignatureService::MAX_AGE );
		self::assertLessThanOrEqual( DAY_IN_SECONDS, SignatureService::MAX_AGE );
	}

	public function test_get_signature_renews_ttl_keeping_issued_at(): void {
		$issuedAt = time() - HOUR_IN_SECONDS;
		$this->givenStored( 'abc', $issuedAt );

		self::assertSame( 'abc', $this->service->getSignature() );
		self::assertSame( 'abc', $this->transients[ self::TRANSIENT_KEY ] );
		self::assertSame( $issuedAt, $this->transients[ self::ISSUED_AT_KEY ] );
		self::assertSame( HOUR_IN_SECONDS, $this->ttls[ self::TRANSIENT_KEY ] );
	}

	public function test_get_signature_regenerates_when_renewal_would_exceed_max_age(): void {
		$this->givenStored( 'abc', time() - SignatureService::MAX_AGE + SignatureService::TTL - 60 );

		$signature = $this->service->getSignature();

		self::assertNotSame( 'abc', $signature );
		self::assertSame( $signature, $this->transients[ self::TRANSIENT_KEY ] );
		self::assertLessThanOrEqual( 5, abs( time() - $this->transients[ self::ISSUED_AT_KEY ] ) );
		self::assertFalse( $this->service->validateSignature( 'abc' ) );
		self::assertTrue( $this->service->validateSignature( $signature ) );
	}

	public function test_sliding_renewal_never_outlives_max_age(): void {
		$this->givenStored( 'abc', time() - SignatureService::MAX_AGE );

		self::assertNotSame( 'abc', $this->service->getSignature() );
	}

	public function test_get_signature_without_generating_does_not_renew_ttl(): void {
		$this->givenStored( 'abc', time() );
		Functions\expect( 'set_transient' )->never();

		self::assertSame( 'abc', $this->service->getSignature( false ) );
	}

	public function test_get_signature_ignores_signature_older_than_max_age(): void {
		$this->givenStored( 'abc', time() - SignatureService::MAX_AGE - 1 );

		self::assertNull( $this->service->getSignature( false ) );
		self::assertFalse( $this->service->validateSignature( 'abc' ) );
	}

	public function test_legacy_signature_without_issued_at_is_replaced_on_render_but_still_validates(): void {
		$this->transients[ self::TRANSIENT_KEY ] = 'abc';

		self::assertSame( 'abc', $this->service->getSignature( false ) );
		self::assertTrue( $this->service->validateSignature( 'abc' ) );

		$this->transients[ self::TRANSIENT_KEY ] = 'abc';
		$renewed                                  = $this->service->getSignature();

		self::assertNotSame( 'abc', $renewed );
		self::assertArrayHasKey( self::ISSUED_AT_KEY, $this->transients );
	}

	public function test_get_signature_returns_null_when_missing_and_not_generating(): void {
		self::assertNull( $this->service->getSignature( false ) );
	}

	public function test_validate_signature_consumes_matching_signature_and_issued_at(): void {
		$this->givenStored( 'abc', time() );

		self::assertTrue( $this->service->validateSignature( 'abc' ) );
		self::assertArrayNotHasKey( self::TRANSIENT_KEY, $this->transients );
		self::assertArrayNotHasKey( self::ISSUED_AT_KEY, $this->transients );
	}

	public function test_validate_signature_rejects_mismatch(): void {
		$this->givenStored( 'abc', time() );

		self::assertFalse( $this->service->validateSignature( 'xyz' ) );
		self::assertSame( 'abc', $this->transients[ self::TRANSIENT_KEY ] );
	}

	public function test_validate_signature_rejects_when_nothing_stored(): void {
		self::assertFalse( $this->service->validateSignature( 'abc' ) );
	}

	private function givenStored( string $signature, int $issuedAt ): void {
		$this->transients[ self::TRANSIENT_KEY ] = $signature;
		$this->transients[ self::ISSUED_AT_KEY ] = $issuedAt;
	}

	private function fakeTransientStore(): void {
		Functions\when( 'get_transient' )->alias(
			function ( $key ) {
				return $this->transients[ $key ] ?? false;
			}
		);
		Functions\when( 'set_transient' )->alias(
			function ( $key, $value, $expiration = 0 ) {
				$this->transients[ $key ] = $value;
				$this->ttls[ $key ]       = $expiration;
				return true;
			}
		);
		Functions\when( 'delete_transient' )->alias(
			function ( $key ) {
				unset( $this->transients[ $key ] );
				return true;
			}
		);
	}
}
