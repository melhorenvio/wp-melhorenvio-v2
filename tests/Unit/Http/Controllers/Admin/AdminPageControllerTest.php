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

	/** @var array<int, array{0: string, 1: mixed, 2: int}> */
	private array $transientWrites = array();

	/** @var string[] Capacidades do usuário atual; padrão é o administrador. */
	private array $capabilities = array( 'manage_options', 'manage_woocommerce' );

	protected function setUp(): void {
		parent::setUp();
		$this->originalEnvBaseUrl = getenv( self::ENV_BASE_URL );
		putenv( self::ENV_BASE_URL );

		Functions\when( 'get_option' )->alias(
			function ( $key, $default = false ) {
				return array_key_exists( $key, $this->options ) ? $this->options[ $key ] : $default;
			}
		);
		Functions\when( 'get_transient' )->alias(
			static function ( $key ) {
				return 'melhor_envio_integrador_signature_issued_at' === $key ? time() : 'stored-signature';
			}
		);
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		Functions\when( 'admin_url' )->alias(
			static function ( $path = '' ) {
				return 'https://loja.example/wp-admin/' . $path;
			}
		);
		Functions\when( 'wp_create_nonce' )->justReturn( 'origin-nonce' );
		Functions\when( 'wp_parse_url' )->alias( 'parse_url' );
		Functions\stubTranslationFunctions();
		Functions\stubEscapeFunctions();
		Functions\when( 'untrailingslashit' )->alias(
			function ( $value ) {
				return rtrim( $value, '/\\' );
			}
		);
		$this->options['home'] = 'https://loja.example';
		Functions\when( 'set_transient' )->alias(
			function ( $key, $value, $expiration ) {
				$this->transientWrites[] = array( $key, $value, $expiration );
				return true;
			}
		);
		Functions\when( 'current_user_can' )->alias(
			function ( $capability ) {
				return in_array( $capability, $this->capabilities, true );
			}
		);
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

	public function test_render_keeps_browser_origin_for_store_at_root(): void {
		$output = $this->render();

		self::assertStringContainsString( 'var storeUrl  = window.location.origin + "";', $output );
	}

	public function test_render_ignores_scheme_and_host_of_home_option(): void {
		$this->options['home'] = 'http://outro-host.example/loja';

		$output = $this->render();

		self::assertStringContainsString( 'var storeUrl  = window.location.origin + "\/loja";', $output );
		self::assertStringNotContainsString( 'outro-host', $output );
	}

	public function test_render_keeps_subdirectory_of_home_in_store_url(): void {
		$this->options['home'] = 'https://exemplo.com.br/loja/';

		$output = $this->render();

		self::assertStringContainsString( 'var storeUrl  = window.location.origin + "\/loja";', $output );
	}

	public function test_render_ignores_language_filter_of_home_url(): void {
		$this->options['home'] = 'https://exemplo.com.br/loja';
		Functions\when( 'home_url' )->justReturn( 'https://exemplo.com.br/loja/en' );
		Functions\when( 'get_home_url' )->justReturn( 'https://exemplo.com.br/loja/en' );

		$output = $this->render();

		self::assertStringContainsString( 'var storeUrl  = window.location.origin + "\/loja";', $output );
	}

	public function test_render_escapes_store_url_injected_into_script(): void {
		$this->options['home'] = 'https://exemplo.com.br/</script><script>alert(1)';

		$output = $this->render();

		self::assertStringNotContainsString( '</script><script>alert(1)', $output );
	}

	public function test_render_generates_signature_when_none_is_stored(): void {
		Functions\when( 'get_transient' )->justReturn( false );
		Functions\when( 'wp_create_nonce' )->justReturn( 'nonce' );
		$this->options['melhor_envio_integrador_signature_key'] = 'key';
		$output = $this->render();

		self::assertMatchesRegularExpression( '/var signature = "[a-f0-9]{64}";/', $output );

		$writes = array_column( $this->transientWrites, 1, 0 );
		self::assertCount( 2, $writes );
		self::assertMatchesRegularExpression( '/^[a-f0-9]{64}$/', $writes['melhor_envio_integrador_signature'] );
		self::assertIsInt( $writes['melhor_envio_integrador_signature_issued_at'] );
	}

	public function test_render_without_signature_for_shop_manager_with_connected_account(): void {
		$this->capabilities = array( 'manage_woocommerce' );
		$this->options['melhor_envio_integrador_secret'] = 'base64:c2VjcmV0';
		Functions\expect( 'get_transient' )->never();
		Functions\expect( 'set_transient' )->never();

		$output = $this->render();

		self::assertStringContainsString( 'var secret    = "base64:c2VjcmV0";', $output );
		self::assertStringContainsString( 'var signature = null;', $output );
		self::assertStringContainsString( "if (signature) {\n\t\t\t\tiframeUrl.searchParams.set('signature', signature);", $output );
	}

	public function test_render_blocks_account_connection_for_shop_manager(): void {
		$this->capabilities = array( 'manage_woocommerce' );
		Functions\expect( 'get_transient' )->never();
		Functions\expect( 'set_transient' )->never();
		$output = $this->render();

		self::assertStringNotContainsString( 'melhor-envio-integrador-container', $output );
		self::assertStringNotContainsString( '<script>', $output );
		self::assertStringContainsString( 'Peça a um administrador da loja para conectá-la.', $output );
	}

	public function test_render_does_not_expose_secret_below_default_capability(): void {
		$this->capabilities = array( 'edit_shop_orders' );
		$this->options['melhor_envio_integrador_secret'] = 'base64:c2VjcmV0';

		$output = $this->render();

		self::assertStringNotContainsString( 'c2VjcmV0', $output );
		self::assertStringNotContainsString( '<script>', $output );
		self::assertStringContainsString( 'Fale com um administrador da loja.', $output );
	}

	public function test_render_only_answers_messages_from_own_iframe(): void {
		$output = $this->render();

		self::assertStringContainsString( 'if (event.source !== iframe.contentWindow) return;', $output );
	}

	public function test_render_lists_backend_domain_as_allowed_app_origin(): void {
		$output = $this->render();

		self::assertStringContainsString( 'var appDomains     = ["woocommerceenvios.com"];', $output );
	}

	public function test_render_logs_signature_sent_to_unknown_origin_without_blocking(): void {
		$output = $this->render();

		self::assertStringContainsString( '"action":"melhor_envio_app_origin_mismatch"', $output );
		self::assertStringContainsString( '"nonce":"origin-nonce"', $output );
		self::assertStringContainsString( 'if (!isAppOrigin(event.origin)) logOriginMismatch(event.origin);', $output );
		self::assertMatchesRegularExpression( '/logOriginMismatch\(event\.origin\);\s+iframe\.contentWindow\.postMessage\(/', $output );
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
