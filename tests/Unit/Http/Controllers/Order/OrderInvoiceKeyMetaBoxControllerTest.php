<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Http\Controllers\Order;

use Brain\Monkey\Functions;
use MelhorEnvio\Http\Controllers\Order\OrderInvoiceKeyMetaBoxController;
use MelhorEnvio\Tests\TestCase;
use Mockery;

final class OrderInvoiceKeyMetaBoxControllerTest extends TestCase {

	private const INVOICE_KEY = '35240612345678000190550010000001231234567890';
	private const ORDER_ID    = 77;

	private OrderInvoiceKeyMetaBoxController $controller;

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'wp_unslash' )->returnArg();
		Functions\when( 'sanitize_text_field' )->alias( 'trim' );
		Functions\stubEscapeFunctions();
		Functions\stubTranslationFunctions();

		$this->controller = new OrderInvoiceKeyMetaBoxController();
	}

	public function test_register_hooks_meta_box_and_order_save(): void {
		$this->controller->register();

		self::assertNotFalse( has_action( 'add_meta_boxes', array( $this->controller, 'addMetaBox' ) ) );
		self::assertNotFalse( has_action( 'woocommerce_process_shop_order_meta', array( $this->controller, 'save' ) ) );
	}

	public function test_adds_meta_box_to_legacy_and_hpos_order_screens(): void {
		Functions\when( 'wc_get_page_screen_id' )->justReturn( 'woocommerce_page_wc-orders' );

		$screens = array();
		Functions\expect( 'add_meta_box' )
			->twice()
			->andReturnUsing(
				function ( $id, $title, $callback, $screen, $context, $priority ) use ( &$screens ): void {
					self::assertSame( 'me_invoice_data', $id );
					self::assertSame( 'Nota Fiscal', $title );
					self::assertSame( array( $this->controller, 'render' ), $callback );
					self::assertSame( 'normal', $context );
					self::assertSame( 'default', $priority );
					$screens[] = $screen;
				}
			);

		$this->controller->addMetaBox();

		self::assertSame( array( 'shop_order', 'woocommerce_page_wc-orders' ), $screens );
	}

	public function test_adds_meta_box_once_when_hpos_screen_is_shop_order(): void {
		Functions\when( 'wc_get_page_screen_id' )->justReturn( 'shop_order' );

		Functions\expect( 'add_meta_box' )
			->once()
			->with( 'me_invoice_data', 'Nota Fiscal', array( $this->controller, 'render' ), 'shop_order', 'normal', 'default' );

		$this->controller->addMetaBox();
	}

	public function test_render_with_hpos_order_shows_stored_key(): void {
		$order = $this->mockOrderWithMeta( self::INVOICE_KEY, '' );
		Functions\expect( 'wc_get_order' )->never();
		Functions\expect( 'wp_nonce_field' )->once()->with( 'me_save_invoice_key', 'me_invoice_key_nonce' );

		$html = $this->renderToString( $order );

		self::assertStringContainsString( 'name="me_invoice_key"', $html );
		self::assertStringContainsString( 'value="' . self::INVOICE_KEY . '"', $html );
		self::assertStringContainsString( 'name="me_nfe_raw_xml"', $html );
		self::assertStringContainsString( 'Arraste ou clique para selecionar', $html );
		self::assertStringNotContainsString( 'XML importado', $html );
	}

	public function test_render_with_legacy_post_loads_order_and_flags_imported_xml(): void {
		$post     = Mockery::mock( 'WP_Post' );
		$post->ID = self::ORDER_ID;

		$order = $this->mockOrderWithMeta( '', '<nfeProc/>' );
		Functions\expect( 'wc_get_order' )->once()->with( self::ORDER_ID )->andReturn( $order );
		Functions\expect( 'wp_nonce_field' )->once()->with( 'me_save_invoice_key', 'me_invoice_key_nonce' );

		$html = $this->renderToString( $post );

		self::assertStringContainsString( 'value=""', $html );
		self::assertStringContainsString( 'XML importado', $html );
		self::assertStringContainsString( 'Clique para substituir', $html );
		self::assertStringNotContainsString( 'Arraste ou clique para selecionar', $html );
	}

	public function test_render_escapes_stored_key(): void {
		$order = $this->mockOrderWithMeta( '"><script>x</script>', '' );
		Functions\when( 'wp_nonce_field' )->justReturn( '' );

		$html = $this->renderToString( $order );

		self::assertStringNotContainsString( '"><script>x</script>', $html );
		self::assertStringContainsString( '&quot;&gt;&lt;script&gt;', $html );
	}

	public function test_render_outputs_nothing_when_order_is_not_found(): void {
		$post     = Mockery::mock( 'WP_Post' );
		$post->ID = self::ORDER_ID;

		Functions\expect( 'wc_get_order' )->once()->with( self::ORDER_ID )->andReturn( false );
		Functions\expect( 'wp_nonce_field' )->never();

		self::assertSame( '', $this->renderToString( $post ) );
	}

	public function test_save_stores_key_and_raw_xml(): void {
		$xml   = '<nfeProc xmlns="http://www.portalfiscal.inf.br/nfe"><NFe/></nfeProc>';
		$_POST = $this->validPost(
			array(
				'me_invoice_key' => self::INVOICE_KEY,
				'me_nfe_raw_xml' => $xml,
			)
		);
		$this->givenAuthorized();

		$order = $this->givenOrder();
		$order->expects( 'update_meta_data' )->with( OrderInvoiceKeyMetaBoxController::META_KEY, self::INVOICE_KEY );
		$order->expects( 'update_meta_data' )->with( '_me_invoice_xml_danfe', $xml );
		$order->expects( 'save' );

		$this->controller->save( self::ORDER_ID );
	}

	public function test_save_strips_non_digit_characters_from_key(): void {
		$_POST = $this->validPost( array( 'me_invoice_key' => '3524 0612 3456 7800 0190 5500 1000 0001 2312 3456 7890' ) );
		$this->givenAuthorized();

		$order = $this->givenOrder();
		$order->expects( 'update_meta_data' )->with( OrderInvoiceKeyMetaBoxController::META_KEY, self::INVOICE_KEY );
		$order->expects( 'save' );

		$this->controller->save( self::ORDER_ID );
	}

	public function test_save_clears_key_when_field_is_empty_or_missing(): void {
		$_POST = $this->validPost( array() );
		$this->givenAuthorized();

		$order = $this->givenOrder();
		$order->expects( 'update_meta_data' )->with( OrderInvoiceKeyMetaBoxController::META_KEY, '' );
		$order->expects( 'save' );

		$this->controller->save( self::ORDER_ID );
	}

	/**
	 * Key length/format is validated by the Melhor Integrador, not by the plugin.
	 */
	public function test_save_persists_key_without_validating_its_length(): void {
		$_POST = $this->validPost( array( 'me_invoice_key' => '12345' ) );
		$this->givenAuthorized();

		$order = $this->givenOrder();
		$order->expects( 'update_meta_data' )->with( OrderInvoiceKeyMetaBoxController::META_KEY, '12345' );
		$order->expects( 'save' );

		$this->controller->save( self::ORDER_ID );
	}

	public function test_save_ignores_xml_larger_than_500_kb(): void {
		$_POST = $this->validPost(
			array(
				'me_invoice_key' => self::INVOICE_KEY,
				'me_nfe_raw_xml' => str_repeat( 'a', 512001 ),
			)
		);
		$this->givenAuthorized();

		$order = $this->givenOrder();
		$order->expects( 'update_meta_data' )->with( OrderInvoiceKeyMetaBoxController::META_KEY, self::INVOICE_KEY );
		$order->shouldNotReceive( 'update_meta_data' )->with( '_me_invoice_xml_danfe', Mockery::any() );
		$order->expects( 'save' );

		$this->controller->save( self::ORDER_ID );
	}

	public function test_save_accepts_xml_of_exactly_500_kb(): void {
		$xml   = str_repeat( 'a', 512000 );
		$_POST = $this->validPost(
			array(
				'me_invoice_key' => self::INVOICE_KEY,
				'me_nfe_raw_xml' => $xml,
			)
		);
		$this->givenAuthorized();

		$order = $this->givenOrder();
		$order->expects( 'update_meta_data' )->with( OrderInvoiceKeyMetaBoxController::META_KEY, self::INVOICE_KEY );
		$order->expects( 'update_meta_data' )->with( '_me_invoice_xml_danfe', $xml );
		$order->expects( 'save' );

		$this->controller->save( self::ORDER_ID );
	}

	public function test_save_keeps_existing_xml_when_field_is_empty(): void {
		$_POST = $this->validPost(
			array(
				'me_invoice_key' => self::INVOICE_KEY,
				'me_nfe_raw_xml' => '',
			)
		);
		$this->givenAuthorized();

		$order = $this->givenOrder();
		$order->expects( 'update_meta_data' )->with( OrderInvoiceKeyMetaBoxController::META_KEY, self::INVOICE_KEY );
		$order->shouldNotReceive( 'update_meta_data' )->with( '_me_invoice_xml_danfe', Mockery::any() );
		$order->expects( 'save' );

		$this->controller->save( self::ORDER_ID );
	}

	public function test_save_bails_without_nonce(): void {
		$_POST = array( 'me_invoice_key' => self::INVOICE_KEY );

		Functions\expect( 'wp_verify_nonce' )->never();
		Functions\expect( 'wc_get_order' )->never();

		$this->controller->save( self::ORDER_ID );
	}

	public function test_save_bails_with_invalid_nonce(): void {
		$_POST = $this->validPost( array( 'me_invoice_key' => self::INVOICE_KEY ) );

		Functions\expect( 'wp_verify_nonce' )->once()->with( 'nonce-value', 'me_save_invoice_key' )->andReturn( false );
		Functions\expect( 'current_user_can' )->never();
		Functions\expect( 'wc_get_order' )->never();

		$this->controller->save( self::ORDER_ID );
	}

	public function test_save_bails_without_capability(): void {
		$_POST = $this->validPost( array( 'me_invoice_key' => self::INVOICE_KEY ) );

		Functions\when( 'wp_verify_nonce' )->justReturn( 1 );
		Functions\expect( 'current_user_can' )->once()->with( 'edit_shop_orders' )->andReturn( false );
		Functions\expect( 'wc_get_order' )->never();

		$this->controller->save( self::ORDER_ID );
	}

	public function test_save_bails_when_order_is_not_found(): void {
		$_POST = $this->validPost( array( 'me_invoice_key' => self::INVOICE_KEY ) );
		$this->givenAuthorized();

		Functions\expect( 'wc_get_order' )->once()->with( self::ORDER_ID )->andReturn( false );

		$this->controller->save( self::ORDER_ID );
	}

	private function validPost( array $fields ): array {
		return array_merge( array( 'me_invoice_key_nonce' => 'nonce-value' ), $fields );
	}

	private function givenAuthorized(): void {
		Functions\expect( 'wp_verify_nonce' )->once()->with( 'nonce-value', 'me_save_invoice_key' )->andReturn( 1 );
		Functions\expect( 'current_user_can' )->once()->with( 'edit_shop_orders' )->andReturn( true );
	}

	/**
	 * @return \Mockery\MockInterface&\WC_Order
	 */
	private function givenOrder() {
		$order = Mockery::mock( 'WC_Order' );
		Functions\expect( 'wc_get_order' )->once()->with( self::ORDER_ID )->andReturn( $order );

		return $order;
	}

	/**
	 * @return \Mockery\MockInterface&\WC_Order
	 */
	private function mockOrderWithMeta( string $invoiceKey, string $xml ) {
		$order = Mockery::mock( 'WC_Order' );
		$order->allows( 'get_meta' )->with( OrderInvoiceKeyMetaBoxController::META_KEY, true )->andReturn( $invoiceKey );
		$order->allows( 'get_meta' )->with( '_me_invoice_xml_danfe', true )->andReturn( $xml );

		return $order;
	}

	/**
	 * @param object $postOrOrder
	 */
	private function renderToString( $postOrOrder ): string {
		ob_start();
		$this->controller->render( $postOrOrder );

		return (string) ob_get_clean();
	}
}
