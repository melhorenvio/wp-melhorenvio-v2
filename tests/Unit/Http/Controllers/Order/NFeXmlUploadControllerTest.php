<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Http\Controllers\Order;

use Brain\Monkey\Functions;
use MelhorEnvio\Http\Controllers\Order\NFeXmlUploadController;
use MelhorEnvio\Http\Controllers\Order\OrderInvoiceKeyMetaBoxController;
use MelhorEnvio\Tests\TestCase;
use Mockery;

final class NFeXmlUploadControllerTest extends TestCase {

	private const INVOICE_KEY = '35240612345678000190550010000001231234567890';
	private const ORDER_ID    = 42;

	private NFeXmlUploadController $controller;

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'wp_unslash' )->returnArg();
		Functions\when( 'sanitize_text_field' )->alias( 'trim' );
		Functions\when( 'absint' )->alias(
			static function ( $value ): int {
				return abs( (int) $value );
			}
		);

		$this->controller = new NFeXmlUploadController();
	}

	protected function tearDown(): void {
		$_FILES = array();
		parent::tearDown();
	}

	public function test_register_hooks_into_private_ajax_action(): void {
		$this->controller->register();

		self::assertNotFalse( has_action( 'wp_ajax_upload_nfe_xml', array( $this->controller, 'handle' ) ) );
		self::assertFalse( has_action( 'wp_ajax_nopriv_upload_nfe_xml', array( $this->controller, 'handle' ) ) );
	}

	public function test_handle_stores_key_and_xml_from_nfe_proc(): void {
		$_POST = array(
			'nonce'    => 'valid-nonce',
			'order_id' => (string) self::ORDER_ID,
		);
		$this->givenUploadedFixture( 'nfe-proc.xml' );

		Functions\expect( 'wp_verify_nonce' )->once()->with( 'valid-nonce', 'upload_nfe_xml' )->andReturn( 1 );
		Functions\expect( 'current_user_can' )->once()->with( 'edit_shop_orders' )->andReturn( true );

		$order = $this->givenOrder();
		$order->expects( 'update_meta_data' )->with( OrderInvoiceKeyMetaBoxController::META_KEY, self::INVOICE_KEY );
		$order->expects( 'update_meta_data' )->with( '_me_invoice_xml_danfe', $this->fixtureContents( 'nfe-proc.xml' ) );
		$order->expects( 'save' );

		Functions\expect( 'wp_send_json_success' )->once()->with( array( 'key' => self::INVOICE_KEY ), 200 );
		Functions\expect( 'wp_send_json_error' )->never();

		$this->controller->handle();
	}

	public function test_handle_rejects_user_without_capability(): void {
		$_POST = array(
			'nonce'    => 'valid-nonce',
			'order_id' => (string) self::ORDER_ID,
		);
		$this->givenUploadedFixture( 'nfe-proc.xml' );

		Functions\when( 'wp_verify_nonce' )->justReturn( 1 );
		Functions\expect( 'current_user_can' )->once()->with( 'edit_shop_orders' )->andReturn( false );
		Functions\expect( 'wc_get_order' )->never();
		Functions\expect( 'wp_send_json_error' )->once()->with( array( 'message' => 'Insufficient permissions.' ), 403 );

		$this->controller->handle();
	}

	public function test_falls_back_to_inf_nfe_id_when_protocol_is_missing(): void {
		$this->givenRequest( 'nfe-without-protocol.xml' );

		$order = $this->givenOrder();
		$order->expects( 'update_meta_data' )->with( OrderInvoiceKeyMetaBoxController::META_KEY, self::INVOICE_KEY );
		$order->expects( 'update_meta_data' )->with( '_me_invoice_xml_danfe', $this->fixtureContents( 'nfe-without-protocol.xml' ) );
		$order->expects( 'save' );

		Functions\expect( 'wp_send_json_success' )->once()->with( array( 'key' => self::INVOICE_KEY ), 200 );

		$this->invokeProcess();
	}

	public function test_prefers_protocol_key_over_inf_nfe_id(): void {
		$this->givenRequest( 'nfe-proc-key-differs-from-id.xml' );

		$order = $this->givenOrder();
		$order->expects( 'update_meta_data' )->with( OrderInvoiceKeyMetaBoxController::META_KEY, self::INVOICE_KEY );
		$order->expects( 'update_meta_data' )->with( '_me_invoice_xml_danfe', Mockery::type( 'string' ) );
		$order->expects( 'save' );

		Functions\expect( 'wp_send_json_success' )->once()->with( array( 'key' => self::INVOICE_KEY ), 200 );

		$this->invokeProcess();
	}

	/**
	 * @dataProvider invalidOrderIds
	 */
	public function test_rejects_invalid_order_id( array $post ): void {
		$_POST = $post;

		Functions\expect( 'wc_get_order' )->never();
		$this->expectJsonError( 'ID do pedido inválido.' );

		$this->invokeProcess();
	}

	public function invalidOrderIds(): array {
		return array(
			'missing'     => array( array() ),
			'zero'        => array( array( 'order_id' => '0' ) ),
			'non numeric' => array( array( 'order_id' => 'abc' ) ),
		);
	}

	public function test_rejects_unknown_order(): void {
		$_POST = array( 'order_id' => (string) self::ORDER_ID );

		Functions\expect( 'wc_get_order' )->once()->with( self::ORDER_ID )->andReturn( false );
		$this->expectJsonError( 'Pedido não encontrado.' );

		$this->invokeProcess();
	}

	public function test_rejects_missing_file(): void {
		$_POST = array( 'order_id' => (string) self::ORDER_ID );
		$this->givenOrder()->shouldNotReceive( 'save' );

		$this->expectJsonError( 'Arquivo não recebido ou erro no upload.' );

		$this->invokeProcess();
	}

	public function test_rejects_upload_error(): void {
		$this->givenRequest( 'nfe-proc.xml' );
		$_FILES['nfe_xml']['error'] = UPLOAD_ERR_PARTIAL;
		$this->givenOrder()->shouldNotReceive( 'save' );

		$this->expectJsonError( 'Arquivo não recebido ou erro no upload.' );

		$this->invokeProcess();
	}

	public function test_rejects_file_larger_than_500_kb(): void {
		$this->givenRequest( 'nfe-proc.xml' );
		$_FILES['nfe_xml']['size'] = 512001;
		$this->givenOrder()->shouldNotReceive( 'save' );

		$this->expectJsonError( 'Arquivo excede o limite de 500 KB.' );

		$this->invokeProcess();
	}

	public function test_accepts_file_of_exactly_500_kb(): void {
		$this->givenRequest( 'nfe-proc.xml' );
		$_FILES['nfe_xml']['size'] = 512000;

		$order = $this->givenOrder();
		$order->allows( 'update_meta_data' );
		$order->expects( 'save' );

		Functions\expect( 'wp_send_json_success' )->once()->with( array( 'key' => self::INVOICE_KEY ), 200 );

		$this->invokeProcess();
	}

	public function test_rejects_non_xml_file(): void {
		$this->givenRequest( 'not-xml.txt' );
		$this->givenOrder()->shouldNotReceive( 'save' );

		$this->expectJsonError( 'O arquivo deve ser um XML válido.' );

		$this->invokeProcess();
	}

	public function test_rejects_malformed_xml(): void {
		$this->givenRequest( 'malformed.xml' );
		$this->givenOrder()->shouldNotReceive( 'save' );

		$this->expectJsonError( 'Não foi possível processar o XML.' );

		$this->invokeProcess();
	}

	public function test_rejects_xml_without_nfe_namespace(): void {
		$this->givenRequest( 'nfe-without-namespace.xml' );
		$this->givenOrder()->shouldNotReceive( 'save' );

		$this->expectJsonError( 'XML não é uma NF-e válida (infNFe não encontrado).' );

		$this->invokeProcess();
	}

	/**
	 * @dataProvider fixturesWithoutValidKey
	 */
	public function test_rejects_nfe_without_valid_key( string $fixture ): void {
		$this->givenRequest( $fixture );
		$this->givenOrder()->shouldNotReceive( 'save' );

		$this->expectJsonError( 'Chave de acesso inválida ou não encontrada no XML.' );

		$this->invokeProcess();
	}

	public function fixturesWithoutValidKey(): array {
		return array(
			'short key in Id' => array( 'nfe-invalid-key.xml' ),
			'no Id attribute' => array( 'nfe-without-key.xml' ),
		);
	}

	private function givenRequest( string $fixture ): void {
		$_POST = array( 'order_id' => (string) self::ORDER_ID );
		$this->givenUploadedFixture( $fixture );
	}

	private function givenUploadedFixture( string $fixture ): void {
		$path = $this->fixturePath( $fixture );

		$_FILES = array(
			'nfe_xml' => array(
				'name'     => $fixture,
				'type'     => 'text/xml',
				'tmp_name' => $path,
				'error'    => UPLOAD_ERR_OK,
				'size'     => filesize( $path ),
			),
		);
	}

	/**
	 * @return \Mockery\MockInterface&\WC_Order
	 */
	private function givenOrder() {
		$order = Mockery::mock( 'WC_Order' );
		Functions\expect( 'wc_get_order' )->once()->with( self::ORDER_ID )->andReturn( $order );

		return $order;
	}

	private function expectJsonError( string $message ): void {
		Functions\expect( 'wp_send_json_error' )->once()->with( array( 'message' => $message ), 422 );
		Functions\expect( 'wp_send_json_success' )->never();
	}

	private function invokeProcess(): void {
		$method = new \ReflectionMethod( NFeXmlUploadController::class, 'process' );
		$method->setAccessible( true );
		$method->invoke( $this->controller );
	}

	private function fixturePath( string $fixture ): string {
		return dirname( __DIR__, 4 ) . '/Fixtures/nfe/' . $fixture;
	}

	private function fixtureContents( string $fixture ): string {
		return (string) file_get_contents( $this->fixturePath( $fixture ) );
	}
}
