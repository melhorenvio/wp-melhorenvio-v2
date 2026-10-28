<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Http\Controllers\Admin;

use Brain\Monkey\Functions;
use MelhorEnvio\Http\Controllers\Admin\AppOriginMismatchController;
use MelhorEnvio\Tests\TestCase;
use Mockery;

final class AppOriginMismatchControllerTest extends TestCase {

	protected function tearDown(): void {
		$_POST = array();
		parent::tearDown();
	}

	public function test_registers_private_ajax_action(): void {
		$controller = new AppOriginMismatchController();
		$controller->register();

		self::assertNotFalse( has_action( 'wp_ajax_melhor_envio_app_origin_mismatch', array( $controller, 'handle' ) ) );
		self::assertFalse( has_action( 'wp_ajax_nopriv_melhor_envio_app_origin_mismatch' ) );
	}

	public function test_logs_unknown_origin_without_signature(): void {
		$_POST = array(
			'nonce'  => 'n',
			'origin' => 'https://evil.example',
		);
		Functions\when( 'wp_unslash' )->returnArg();
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\expect( 'wp_verify_nonce' )->once()->with( 'n', 'melhor_envio_app_origin_mismatch' )->andReturn( 1 );
		Functions\expect( 'current_user_can' )->once()->with( 'manage_woocommerce' )->andReturn( true );

		$logger = Mockery::mock();
		$logger->shouldReceive( 'warning' )
			->once()
			->with(
				Mockery::type( 'string' ),
				array(
					'source' => 'melhor-envio-cotacao',
					'origin' => 'https://evil.example',
				)
			);
		Functions\when( 'wc_get_logger' )->justReturn( $logger );
		Functions\expect( 'wp_send_json_success' )->once()->with( null, 200 );

		( new AppOriginMismatchController() )->handle();
	}

	public function test_truncates_long_origin(): void {
		$_POST = array(
			'nonce'  => 'n',
			'origin' => 'https://' . str_repeat( 'a', 300 ),
		);
		Functions\when( 'wp_unslash' )->returnArg();
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'wp_verify_nonce' )->justReturn( 1 );
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'wp_send_json_success' )->justReturn( null );

		$logger = Mockery::mock();
		$logger->shouldReceive( 'warning' )
			->once()
			->withArgs( static fn( $message, $context ) => strlen( $context['origin'] ) === 200 );
		Functions\when( 'wc_get_logger' )->justReturn( $logger );

		( new AppOriginMismatchController() )->handle();
	}
}
