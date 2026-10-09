<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Http\Controllers\Concerns;

use Brain\Monkey\Functions;
use MelhorEnvio\Http\Controllers\Concerns\ValidatesAuthentication;
use MelhorEnvio\Services\Auth\SecretService;
use MelhorEnvio\Services\Auth\SignatureService;
use MelhorEnvio\Tests\TestCase;
use WP_REST_Request;

final class ValidatesAuthenticationTest extends TestCase {

	private const SIGNATURE_TRANSIENT = 'melhor_envio_integrador_signature';
	private const SECRET_OPTION       = 'melhor_envio_integrador_secret';

	public function test_signature_is_accepted_when_it_matches_stored_one(): void {
		Functions\expect( 'get_transient' )->once()->with( self::SIGNATURE_TRANSIENT )->andReturn( 'sig-123' );
		Functions\expect( 'delete_transient' )->once()->with( self::SIGNATURE_TRANSIENT )->andReturn( true );

		$guard = $this->makeGuard( new SignatureService(), null );

		self::assertTrue( $guard->checkSignaturePermission( $this->requestWith( 'X-ME-Signature', 'sig-123' ) ) );
	}

	public function test_signature_is_rejected_when_it_differs_from_stored_one(): void {
		Functions\when( 'get_transient' )->justReturn( 'sig-123' );
		Functions\expect( 'delete_transient' )->never();

		$guard = $this->makeGuard( new SignatureService(), null );

		self::assertFalse( $guard->checkSignaturePermission( $this->requestWith( 'X-ME-Signature', 'other' ) ) );
	}

	public function test_signature_is_rejected_when_header_missing(): void {
		Functions\expect( 'get_transient' )->never();

		$guard = $this->makeGuard( new SignatureService(), null );

		self::assertFalse( $guard->checkSignaturePermission( new WP_REST_Request( 'GET', '/x' ) ) );
		self::assertFalse( $guard->checkSignaturePermission( $this->requestWith( 'X-ME-Signature', '' ) ) );
	}

	public function test_signature_is_rejected_when_no_signature_service(): void {
		Functions\expect( 'get_transient' )->never();

		$guard = $this->makeGuard( null, new SecretService() );

		self::assertFalse( $guard->checkSignaturePermission( $this->requestWith( 'X-ME-Signature', 'sig-123' ) ) );
	}

	public function test_secret_is_accepted_when_it_matches_stored_one(): void {
		Functions\expect( 'get_option' )->once()->with( self::SECRET_OPTION )->andReturn( 's3cr3t' );

		$guard = $this->makeGuard( null, new SecretService() );

		self::assertTrue( $guard->checkSecretPermission( $this->requestWith( 'X-ME-Secret', 's3cr3t' ) ) );
	}

	public function test_secret_is_rejected_when_it_differs_from_stored_one(): void {
		Functions\when( 'get_option' )->justReturn( 's3cr3t' );

		$guard = $this->makeGuard( null, new SecretService() );

		self::assertFalse( $guard->checkSecretPermission( $this->requestWith( 'X-ME-Secret', 'wrong' ) ) );
	}

	public function test_secret_is_rejected_when_none_is_stored(): void {
		Functions\when( 'get_option' )->justReturn( false );

		$guard = $this->makeGuard( null, new SecretService() );

		self::assertFalse( $guard->checkSecretPermission( $this->requestWith( 'X-ME-Secret', 'anything' ) ) );
	}

	public function test_secret_is_rejected_when_header_missing(): void {
		Functions\expect( 'get_option' )->never();

		$guard = $this->makeGuard( null, new SecretService() );

		self::assertFalse( $guard->checkSecretPermission( new WP_REST_Request( 'GET', '/x' ) ) );
	}

	public function test_secret_is_rejected_when_no_secret_service(): void {
		Functions\expect( 'get_option' )->never();

		$guard = $this->makeGuard( new SignatureService(), null );

		self::assertFalse( $guard->checkSecretPermission( $this->requestWith( 'X-ME-Secret', 's3cr3t' ) ) );
	}

	/**
	 * @return object
	 */
	private function makeGuard( ?SignatureService $signature, ?SecretService $secret ) {
		return new class( $signature, $secret ) {

			use ValidatesAuthentication;

			public function __construct( ?SignatureService $signature, ?SecretService $secret ) {
				$this->signatureManager = $signature;
				$this->secretManager    = $secret;
			}
		};
	}

	private function requestWith( string $header, string $value ): WP_REST_Request {
		$request = new WP_REST_Request( 'POST', '/x' );
		$request->set_header( $header, $value );

		return $request;
	}
}
