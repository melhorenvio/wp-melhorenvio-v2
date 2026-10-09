<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Http\Controllers\Frontend;

use Brain\Monkey\Functions;
use MelhorEnvio\Http\Controllers\Frontend\ProductShippingCalculatorController;
use MelhorEnvio\Services\Settings\IntegradorSettingsService;
use MelhorEnvio\Tests\TestCase;
use Mockery;

/**
 * IntegradorSettingsService is final, so the real service is used with get_option() faked.
 */
final class ProductShippingCalculatorControllerTest extends TestCase {

	private const DEFAULT_HOOK = 'woocommerce_before_add_to_cart_button';

	private ProductShippingCalculatorController $controller;

	/** @var array<string, mixed> */
	private array $options = array();

	/** @var string[] */
	private array $readOptions = array();

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'get_option' )->alias(
			function ( string $name, $default = false ) {
				$this->readOptions[] = $name;
				return array_key_exists( $name, $this->options ) ? $this->options[ $name ] : $default;
			}
		);

		$this->options = array(
			'melhor_envio_ui_mode'                    => 'integrador',
			'melhor_envio_integrador_quotation_token' => 'token',
		);

		$this->controller = new ProductShippingCalculatorController( new IntegradorSettingsService() );
	}

	protected function tearDown(): void {
		unset( $GLOBALS['product'] );
		parent::tearDown();
	}

	/*
	 * register
	 */

	public function test_register_renders_widget_before_add_to_cart_button_by_default(): void {
		$this->controller->register();

		self::assertNotFalse( has_action( self::DEFAULT_HOOK, array( $this->controller, 'renderWidget' ) ) );
	}

	public function test_register_uses_configured_position(): void {
		$this->options['melhor_envio_integrador_settings'] = array(
			'calculator' => array(
				'enabled'  => true,
				'position' => 'woocommerce_after_add_to_cart_form',
			),
		);

		$this->controller->register();

		self::assertNotFalse( has_action( 'woocommerce_after_add_to_cart_form', array( $this->controller, 'renderWidget' ) ) );
		self::assertFalse( has_action( self::DEFAULT_HOOK, array( $this->controller, 'renderWidget' ) ) );
	}

	public function test_register_uses_legacy_position_when_settings_were_never_saved(): void {
		$this->options['melhor_envio_option_where_show_calculator'] = 'woocommerce_after_single_product_summary';

		$this->controller->register();

		self::assertNotFalse( has_action( 'woocommerce_after_single_product_summary', array( $this->controller, 'renderWidget' ) ) );
	}

	/**
	 * @dataProvider disabledScenarios
	 */
	public function test_register_does_not_render_widget_when( array $options ): void {
		$this->options = array_merge( $this->options, $options );

		$this->controller->register();

		self::assertFalse( has_action( self::DEFAULT_HOOK, array( $this->controller, 'renderWidget' ) ) );
	}

	public function disabledScenarios(): array {
		return array(
			'legacy plugin mode'          => array( array( 'melhor_envio_ui_mode' => 'legacy' ) ),
			'calculator disabled'         => array(
				array(
					'melhor_envio_integrador_settings' => array( 'calculator' => array( 'enabled' => false ) ),
				),
			),
			'calculator hidden in legacy' => array( array( 'melhorenvio_hide_calculator_product' => '1' ) ),
			'missing quotation token'     => array( array( 'melhor_envio_integrador_quotation_token' => '' ) ),
		);
	}

	public function test_register_does_not_read_settings_outside_integrador_mode(): void {
		$this->options['melhor_envio_ui_mode'] = 'legacy';

		$this->controller->register();

		self::assertSame( array( 'melhor_envio_ui_mode' ), $this->readOptions );
	}

	/*
	 * renderWidget
	 */

	public function test_render_widget_outputs_calculator_and_enqueues_assets(): void {
		$this->defineAssetConstants();
		Functions\stubTranslationFunctions();
		Functions\stubEscapeFunctions();
		Functions\when( 'is_product' )->justReturn( true );
		Functions\when( 'admin_url' )->alias(
			static function ( string $path ): string {
				return 'https://example.test/wp-admin/' . $path;
			}
		);
		Functions\expect( 'wp_create_nonce' )->once()->with( 'me_quote' )->andReturn( 'nonce-123' );
		Functions\expect( 'wp_enqueue_style' )
			->once()
			->with( 'me-shipping-calculator', MELHORENVIO_URL . '/assets/css/me-shipping-calculator.css', array(), MELHORENVIO_VERSION );
		Functions\expect( 'wp_enqueue_script' )
			->once()
			->with( 'me-shipping-calculator', MELHORENVIO_URL . '/assets/js/me-shipping-calculator.js', array( 'jquery' ), MELHORENVIO_VERSION, true );
		Functions\expect( 'wp_localize_script' )
			->once()
			->with(
				'me-shipping-calculator',
				'meSC',
				array(
					'ajaxUrl'   => 'https://example.test/wp-admin/admin-ajax.php',
					'nonce'     => 'nonce-123',
					'productId' => 42,
				)
			);

		$GLOBALS['product'] = $this->mockProduct( false );

		$html = $this->render();

		self::assertStringContainsString( 'id="me-cep-calc"', $html );
		self::assertStringContainsString( 'data-product-id="42"', $html );
		self::assertStringContainsString( 'Calcular frete', $html );
		self::assertStringContainsString( 'id="me-cep-input"', $html );
		self::assertStringContainsString( 'maxlength="9"', $html );
		self::assertStringContainsString( 'id="me-cep-result"', $html );
	}

	public function test_render_widget_outputs_nothing_outside_product_pages(): void {
		Functions\when( 'is_product' )->justReturn( false );
		$GLOBALS['product'] = $this->mockProduct( false );

		$this->assertRendersNothing();
	}

	public function test_render_widget_outputs_nothing_without_global_product(): void {
		Functions\when( 'is_product' )->justReturn( true );
		$GLOBALS['product'] = 'some-product-slug';

		$this->assertRendersNothing();
	}

	public function test_render_widget_outputs_nothing_for_virtual_products(): void {
		Functions\when( 'is_product' )->justReturn( true );
		$GLOBALS['product'] = $this->mockProduct( true );

		$this->assertRendersNothing();
	}

	/*
	 * Helpers
	 */

	private function render(): string {
		ob_start();
		$this->controller->renderWidget();

		return (string) ob_get_clean();
	}

	private function assertRendersNothing(): void {
		Functions\expect( 'wp_enqueue_style' )->never();
		Functions\expect( 'wp_enqueue_script' )->never();
		Functions\expect( 'wp_localize_script' )->never();

		self::assertSame( '', $this->render() );
	}

	/**
	 * @return \Mockery\MockInterface&\WC_Product
	 */
	private function mockProduct( bool $virtual ) {
		$product = Mockery::mock( 'WC_Product' );
		$product->allows( 'get_id' )->andReturn( 42 );
		$product->allows( 'is_virtual' )->andReturn( $virtual );

		return $product;
	}

	private function defineAssetConstants(): void {
		if ( ! defined( 'MELHORENVIO_URL' ) ) {
			define( 'MELHORENVIO_URL', 'https://example.test/wp-content/plugins/melhor-envio-cotacao' );
		}

		if ( ! defined( 'MELHORENVIO_VERSION' ) ) {
			define( 'MELHORENVIO_VERSION', '9.9.9' );
		}
	}
}
