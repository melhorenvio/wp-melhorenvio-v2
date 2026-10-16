<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Http\Controllers;

use Brain\Monkey\Functions;
use MelhorEnvio\Tests\TestCase;
use MelhorEnvio\Tests\Unit\Http\Controllers\Fixtures\FakeAjaxHandler;

require_once __DIR__ . '/Fixtures/ContractFixtures.php';

final class AjaxHandlerContractTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\stubs(
			array(
				'sanitize_text_field' => static function ( $value ) {
					return trim( (string) $value );
				},
				'wp_unslash'          => static function ( $value ) {
					return is_string( $value ) ? stripslashes( $value ) : $value;
				},
			)
		);
	}

	public function test_register_hooks_private_ajax_action_by_default(): void {
		$handler = $this->makeHandler( 'do_thing' );

		$handler->register();

		self::assertSame( 10, has_action( 'wp_ajax_do_thing', array( $handler, 'handle' ) ) );
		self::assertFalse( has_action( 'wp_ajax_nopriv_do_thing', array( $handler, 'handle' ) ) );
	}

	public function test_register_hooks_nopriv_ajax_action_when_public(): void {
		$handler = $this->makeHandler( 'do_thing', 'manage_options', true );

		$handler->register();

		self::assertSame( 10, has_action( 'wp_ajax_nopriv_do_thing', array( $handler, 'handle' ) ) );
		self::assertFalse( has_action( 'wp_ajax_do_thing', array( $handler, 'handle' ) ) );
	}

	public function test_handle_rejects_invalid_nonce(): void {
		$_POST['nonce'] = 'bad';
		Functions\expect( 'wp_verify_nonce' )->once()->with( 'bad', 'do_thing' )->andReturn( false );
		Functions\expect( 'current_user_can' )->never();
		Functions\expect( 'wp_send_json_error' )->once()->with( array( 'message' => 'Invalid nonce.' ), 403 );

		$handler = $this->makeHandler( 'do_thing' );
		$handler->handle();

		self::assertSame( 0, $handler->processed );
	}

	public function test_handle_uses_empty_nonce_when_missing(): void {
		Functions\expect( 'wp_verify_nonce' )->once()->with( '', 'do_thing' )->andReturn( false );
		Functions\expect( 'wp_send_json_error' )->once();

		$handler = $this->makeHandler( 'do_thing' );
		$handler->handle();

		self::assertSame( 0, $handler->processed );
	}

	public function test_handle_rejects_user_without_capability(): void {
		$_POST['nonce'] = 'ok';
		Functions\when( 'wp_verify_nonce' )->justReturn( 1 );
		Functions\expect( 'current_user_can' )->once()->with( 'edit_shop_orders' )->andReturn( false );
		Functions\expect( 'wp_send_json_error' )->once()->with( array( 'message' => 'Insufficient permissions.' ), 403 );

		$handler = $this->makeHandler( 'do_thing', 'edit_shop_orders' );
		$handler->handle();

		self::assertSame( 0, $handler->processed );
	}

	public function test_handle_processes_when_nonce_and_capability_are_valid(): void {
		$_POST['nonce'] = 'ok';
		Functions\when( 'wp_verify_nonce' )->justReturn( 1 );
		Functions\expect( 'current_user_can' )->once()->with( 'manage_options' )->andReturn( true );
		Functions\expect( 'wp_send_json_error' )->never();

		$handler = $this->makeHandler( 'do_thing' );
		$handler->handle();

		self::assertSame( 1, $handler->processed );
	}

	public function test_handle_skips_capability_check_when_public(): void {
		$_POST['nonce'] = 'ok';
		Functions\when( 'wp_verify_nonce' )->justReturn( 1 );
		Functions\expect( 'current_user_can' )->never();

		$handler = $this->makeHandler( 'do_thing', 'manage_options', true );
		$handler->handle();

		self::assertSame( 1, $handler->processed );
	}

	public function test_get_input_returns_sanitized_post_value(): void {
		$_POST['name'] = "  O\\'Brien ";

		self::assertSame( "O'Brien", $this->makeHandler( 'do_thing' )->input( 'name' ) );
	}

	public function test_get_input_returns_default_when_missing(): void {
		$handler = $this->makeHandler( 'do_thing' );

		self::assertNull( $handler->input( 'missing' ) );
		self::assertSame( 'fallback', $handler->input( 'missing', 'fallback' ) );
	}

	public function test_send_success_delegates_to_wp_send_json_success(): void {
		Functions\expect( 'wp_send_json_success' )->once()->with( array( 'ok' => true ), 200 );
		Functions\expect( 'wp_send_json_success' )->once()->with( 'created', 201 );

		$handler = $this->makeHandler( 'do_thing' );
		$handler->success( array( 'ok' => true ) );
		$handler->success( 'created', 201 );
	}

	public function test_send_error_delegates_to_wp_send_json_error(): void {
		Functions\expect( 'wp_send_json_error' )->once()->with( array( 'message' => 'x' ), 400 );
		Functions\expect( 'wp_send_json_error' )->once()->with( 'gone', 410 );

		$handler = $this->makeHandler( 'do_thing' );
		$handler->error( array( 'message' => 'x' ) );
		$handler->error( 'gone', 410 );
	}

	private function makeHandler( string $action, string $capability = 'manage_options', bool $public = false ): FakeAjaxHandler {
		return new FakeAjaxHandler( $action, $capability, $public );
	}
}
