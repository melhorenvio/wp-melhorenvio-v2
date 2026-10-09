<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Http\Controllers\Order;

use Brain\Monkey\Functions;
use MelhorEnvio\Http\Controllers\Order\OrderInvoiceKeyMetaBoxController;
use MelhorEnvio\Http\Controllers\Order\OrderNoteInvoiceKeyController;
use MelhorEnvio\Tests\TestCase;
use Mockery;

final class OrderNoteInvoiceKeyControllerTest extends TestCase {

	private const INVOICE_KEY = '35240612345678000190550010000001231234567890';

	private OrderNoteInvoiceKeyController $controller;

	protected function setUp(): void {
		parent::setUp();
		$this->controller = new OrderNoteInvoiceKeyController();
	}

	public function test_register_hooks_into_order_note_added(): void {
		$this->controller->register();

		self::assertNotFalse(
			has_action( 'woocommerce_order_note_added', array( $this->controller, 'extractInvoiceKey' ) )
		);
	}

	public function test_saves_invoice_key_found_in_note(): void {
		$this->givenNote( 'NF-e emitida. Chave de acesso: ' . self::INVOICE_KEY );

		$order = $this->mockOrder( '' );
		$order->expects( 'update_meta_data' )->with( OrderInvoiceKeyMetaBoxController::META_KEY, self::INVOICE_KEY );
		$order->expects( 'save' );

		$this->controller->extractInvoiceKey( 10, $order );
	}

	public function test_skips_when_key_is_already_stored(): void {
		$this->givenNote( 'Chave: ' . self::INVOICE_KEY );

		$order = $this->mockOrder( self::INVOICE_KEY );
		$order->shouldNotReceive( 'update_meta_data' );
		$order->shouldNotReceive( 'save' );

		$this->controller->extractInvoiceKey( 10, $order );
	}

	/**
	 * @dataProvider notesWithoutValidKey
	 */
	public function test_ignores_notes_without_valid_key( string $content ): void {
		$this->givenNote( $content );

		$order = $this->mockOrder( '' );
		$order->shouldNotReceive( 'update_meta_data' );

		$this->controller->extractInvoiceKey( 10, $order );
	}

	public function notesWithoutValidKey(): array {
		return array(
			'no digits'           => array( 'Pedido enviado.' ),
			'model 65 (NFC-e)'    => array( 'Chave: 35240612345678000190650010000001231234567890' ),
			'too short'           => array( 'Chave: 3524061234567800019055001000000123123456789' ),
			'embedded in number'  => array( 'Ref: 9' . self::INVOICE_KEY ),
		);
	}

	public function test_ignores_non_order_argument(): void {
		Functions\expect( 'get_comment' )->never();

		$this->controller->extractInvoiceKey( 10, new \stdClass() );
	}

	public function test_ignores_missing_comment(): void {
		Functions\when( 'get_comment' )->justReturn( null );

		$order = Mockery::mock( 'WC_Order' );
		$order->shouldNotReceive( 'update_meta_data' );

		$this->controller->extractInvoiceKey( 10, $order );
	}

	private function givenNote( string $content ): void {
		$comment                  = Mockery::mock( 'WP_Comment' );
		$comment->comment_content = $content;

		Functions\expect( 'get_comment' )->with( 10 )->andReturn( $comment );
	}

	/**
	 * @return \Mockery\MockInterface&\WC_Order
	 */
	private function mockOrder( string $storedKey ) {
		$order = Mockery::mock( 'WC_Order' );
		$order->allows( 'get_meta' )->with( OrderInvoiceKeyMetaBoxController::META_KEY, true )->andReturn( $storedKey );

		return $order;
	}
}
