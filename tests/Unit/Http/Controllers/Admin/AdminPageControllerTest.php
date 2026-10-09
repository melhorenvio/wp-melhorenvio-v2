<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Http\Controllers\Admin;

use Brain\Monkey\Functions;
use MelhorEnvio\Http\Controllers\Admin\AdminPageController;
use MelhorEnvio\Services\Auth\SecretService;
use MelhorEnvio\Services\Auth\SignatureService;
use MelhorEnvio\Tests\TestCase;

final class AdminPageControllerTest extends TestCase {

	private const ENV_BASE_URL = 'MELHOR_INTEGRADOR_BASE_URL';

	/** @var string|false */
	private $originalEnvBaseUrl;

	/** @var array<string, mixed> */
	private array $options = array();

	protected function setUp(): void {
		parent::setUp();
		$this->originalEnvBaseUrl = getenv( self::ENV_BASE_URL );
		putenv( self::ENV_BASE_URL );

		Functions\when( 'get_option' )->alias(
			function ( $key, $default = false ) {
				return array_key_exists( $key, $this->options ) ? $this->options[ $key ] : $default;
			}
		);
		Functions\when( 'get_transient' )->justReturn( 'stored-signature' );
	}

	protected function tearDown(): void {
		if ( $this->originalEnvBaseUrl === false ) {
			putenv( self::ENV_BASE_URL );
		} else {
			putenv( self::ENV_BASE_URL . '=' . $this->originalEnvBaseUrl );
		}
		parent::tearDown();
	}

	public function test_render_outputs_container_and_iframe_bootstrap_script(): void {
		$output = $this->render();

		self::assertStringContainsString( 'id="melhor-envio-integrador-container"', $output );
		self::assertStringContainsString( '<script>', $output );
		self::assertStringContainsString( "iframeUrl.pathname.replace(/\\/$/, '') + '/wp'", $output );
	}

	public function test_render_exposes_stored_secret_and_signature(): void {
		$this->options['melhor_envio_integrador_secret'] = 'base64:c2VjcmV0';

		$output = $this->render();

		self::assertStringContainsString( 'var hasSecret = true;', $output );
		self::assertStringContainsString( 'var secret    = "base64:c2VjcmV0";', $output );
		self::assertStringContainsString( 'var signature = "stored-signature";', $output );
	}

	public function test_render_without_secret_exposes_null_secret(): void {
		$output = $this->render();

		self::assertStringContainsString( 'var hasSecret = false;', $output );
		self::assertStringContainsString( 'var secret    = null;', $output );
	}

	public function test_render_escapes_values_injected_into_script(): void {
		$this->options['melhor_envio_integrador_secret'] = '</script><script>alert("x")&\'';

		$output = $this->render();

		self::assertStringNotContainsString( '</script><script>alert', $output );
		$u = '\\' . 'u00';
		self::assertStringContainsString(
			"var secret    = \"{$u}3C\\/script{$u}3E{$u}3Cscript{$u}3Ealert({$u}22x{$u}22){$u}26{$u}27\";",
			$output
		);
	}

	public function test_render_uses_default_base_url_when_not_configured(): void {
		$output = $this->render();

		self::assertStringContainsString( 'var baseUrl   = "https:\/\/woocommerceenvios.com";', $output );
	}

	public function test_render_uses_base_url_option_when_env_is_missing(): void {
		$this->options['melhor_integrador_base_url'] = 'https://option.example';

		$output = $this->render();

		self::assertStringContainsString( 'var baseUrl   = "https:\/\/option.example";', $output );
	}

	public function test_render_prefers_base_url_from_environment(): void {
		putenv( self::ENV_BASE_URL . '=https://env.example' );
		$this->options['melhor_integrador_base_url'] = 'https://option.example';

		$output = $this->render();

		self::assertStringContainsString( 'var baseUrl   = "https:\/\/env.example";', $output );
	}

	public function test_render_generates_signature_when_none_is_stored(): void {
		Functions\when( 'get_transient' )->justReturn( false );
		Functions\when( 'wp_create_nonce' )->justReturn( 'nonce' );
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		$this->options['melhor_envio_integrador_signature_key'] = 'key';
		Functions\expect( 'set_transient' )->once()->andReturn( true );

		$output = $this->render();

		self::assertMatchesRegularExpression( '/var signature = "[a-f0-9]{64}";/', $output );
	}

	private function render(): string {
		$controller = new AdminPageController( 'melhor-integrador', new SecretService(), new SignatureService() );

		ob_start();
		try {
			$controller->render();
		} finally {
			$output = (string) ob_get_clean();
		}

		return $output;
	}
}
