<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Services\Auth;

use Brain\Monkey\Filters;
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
		Functions\when( 'get_option' )->justReturn( 'secret' );

		Functions\expect( 'set_transient' )
			->once()
			->with(
				self::TRANSIENT_KEY,
				Mockery::on(
					static function ( $data ) {
						return is_array( $data )
							&& preg_match( '/^[a-f0-9]{64}$/', $data['signature'] )
							&& abs( time() - $data['issued_at'] ) <= 5;
					}
				),
				HOUR_IN_SECONDS
			)
			->andReturn( true );

		$signature = $this->service->generateSignature();

		self::assertMatchesRegularExpression( '/^[a-f0-9]{64}$/', $signature );
	}

	public function test_generate_signature_creates_secret_when_missing(): void {
		Functions\when( 'wp_create_nonce' )->justReturn( 'nonce' );
		Functions\when( 'set_transient' )->justReturn( true );
		Functions\when( 'get_option' )->justReturn( false );
		Functions\when( 'wp_generate_password' )->justReturn( 'generated' );

		Functions\expect( 'update_option' )->once()->with( 'melhor_envio_integrador_signature_key', 'generated' );

		$this->service->generateSignature();

		$this->assertMockExpectationsMet();
	}

	public function test_ttl_is_short_and_ignores_auth_cookie_expiration(): void {
		Functions\when( 'wp_create_nonce' )->justReturn( 'nonce' );
		Functions\when( 'get_option' )->justReturn( 'secret' );
		Functions\when( 'set_transient' )->justReturn( true );

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
		Functions\when( 'get_transient' )->justReturn( $this->stored( 'abc', $issuedAt ) );
		Functions\expect( 'set_transient' )
			->once()
			->with( self::TRANSIENT_KEY, $this->stored( 'abc', $issuedAt ), HOUR_IN_SECONDS )
			->andReturn( true );

		self::assertSame( 'abc', $this->service->getSignature() );
	}

	public function test_get_signature_regenerates_when_renewal_would_exceed_max_age(): void {
		$transient = $this->stored( 'abc', time() - SignatureService::MAX_AGE + SignatureService::TTL - 60 );
		$this->fakeTransientStore( $transient );
		Functions\when( 'wp_create_nonce' )->justReturn( 'nonce' );
		Functions\when( 'get_option' )->justReturn( 'secret' );

		$signature = $this->service->getSignature();

		self::assertNotSame( 'abc', $signature );
		self::assertSame( $signature, $this->transient['signature'] );
		self::assertLessThanOrEqual( 5, abs( time() - $this->transient['issued_at'] ) );
		self::assertFalse( $this->service->validateSignature( 'abc' ) );
		self::assertTrue( $this->service->validateSignature( $signature ) );
	}

	public function test_sliding_renewal_never_outlives_max_age(): void {
		$this->fakeTransientStore( $this->stored( 'abc', time() - SignatureService::MAX_AGE ) );
		Functions\when( 'wp_create_nonce' )->justReturn( 'nonce' );
		Functions\when( 'get_option' )->justReturn( 'secret' );

		self::assertNotSame( 'abc', $this->service->getSignature() );
	}

	public function test_get_signature_without_generating_does_not_renew_ttl(): void {
		Functions\when( 'get_transient' )->justReturn( $this->stored( 'abc', time() ) );
		Functions\expect( 'set_transient' )->never();

		self::assertSame( 'abc', $this->service->getSignature( false ) );
	}

	public function test_get_signature_ignores_signature_older_than_max_age(): void {
		Functions\when( 'get_transient' )->justReturn( $this->stored( 'abc', time() - SignatureService::MAX_AGE - 1 ) );

		self::assertNull( $this->service->getSignature( false ) );
		self::assertFalse( $this->service->validateSignature( 'abc' ) );
	}

	public function test_legacy_string_signature_is_replaced_on_render_but_still_validates(): void {
		Functions\when( 'get_transient' )->justReturn( 'abc' );
		Functions\when( 'wp_create_nonce' )->justReturn( 'nonce' );
		Functions\when( 'get_option' )->justReturn( 'secret' );
		Functions\expect( 'set_transient' )->once()->andReturn( true );
		Functions\expect( 'delete_transient' )->once()->with( self::TRANSIENT_KEY )->andReturn( true );

		self::assertSame( 'abc', $this->service->getSignature( false ) );
		self::assertNotSame( 'abc', $this->service->getSignature() );
		self::assertTrue( $this->service->validateSignature( 'abc' ) );
	}

	public function test_get_signature_returns_null_when_missing_and_not_generating(): void {
		Functions\when( 'get_transient' )->justReturn( false );

		self::assertNull( $this->service->getSignature( false ) );
	}

	public function test_validate_signature_consumes_matching_signature(): void {
		Functions\when( 'get_transient' )->justReturn( $this->stored( 'abc', time() ) );
		Functions\expect( 'delete_transient' )->once()->with( self::TRANSIENT_KEY )->andReturn( true );

		self::assertTrue( $this->service->validateSignature( 'abc' ) );
	}

	public function test_validate_signature_rejects_mismatch(): void {
		Functions\when( 'get_transient' )->justReturn( $this->stored( 'abc', time() ) );
		Functions\expect( 'delete_transient' )->never();

		self::assertFalse( $this->service->validateSignature( 'xyz' ) );
	}

	public function test_validate_signature_rejects_when_nothing_stored(): void {
		Functions\when( 'get_transient' )->justReturn( false );

		self::assertFalse( $this->service->validateSignature( 'abc' ) );
	}

	/** @var mixed */
	private $transient = false;

	private function stored( string $signature, int $issuedAt ): array {
		return array(
			'signature' => $signature,
			'issued_at' => $issuedAt,
		);
	}

	/**
	 * @param mixed $initial
	 */
	private function fakeTransientStore( $initial ): void {
		$this->transient = $initial;

		Functions\when( 'get_transient' )->alias(
			function () {
				return $this->transient;
			}
		);
		Functions\when( 'set_transient' )->alias(
			function ( $key, $value ) {
				$this->transient = $value;
				return true;
			}
		);
		Functions\when( 'delete_transient' )->alias(
			function () {
				$this->transient = false;
				return true;
			}
		);
	}
}
