<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Http\Controllers\Order;

use Brain\Monkey\Filters;
use MelhorEnvio\Http\Controllers\Order\InvoiceProviderKeyController;
use MelhorEnvio\Http\Controllers\Order\OrderInvoiceKeyMetaBoxController;
use MelhorEnvio\Services\Order\InvoiceKeyResolverService;
use MelhorEnvio\Tests\TestCase;
use Mockery;

final class InvoiceProviderKeyControllerTest extends TestCase {

	private const KEY          = '35240612345678000190550010000001231234567890';
	private const REISSUED_KEY = '35240612345678000190550010000001241234567891';
	private const MANUAL_KEY   = '41240698765432000110550020000004561987654321';

	private InvoiceProviderKeyController $controller;

	protected function setUp(): void {
		parent::setUp();
		$this->controller = new InvoiceProviderKeyController( new InvoiceKeyResolverService() );
	}

	public function test_register_hooks_into_before_order_object_save(): void {
		$this->controller->register();

		self::assertSame(
			10,
			has_action( 'woocommerce_before_order_object_save', array( $this->controller, 'applyInvoiceKey' ) )
		);
	}

	public function test_stores_approved_key_without_saving_order(): void {
		$order = $this->mockOrder( array( $this->record( 'aprovado', self::KEY ) ), '' );
		$order->expects( 'update_meta_data' )->with( OrderInvoiceKeyMetaBoxController::META_KEY, self::KEY );
		$order->shouldNotReceive( 'save' );

		$this->controller->applyInvoiceKey( $order );
	}

	public function test_skips_when_key_is_already_stored(): void {
		$order = $this->mockOrder( array( $this->record( 'aprovado', self::KEY ) ), self::KEY );
		$order->shouldNotReceive( 'update_meta_data' );
		$order->shouldNotReceive( 'save' );

		$this->controller->applyInvoiceKey( $order );
	}

	public function test_keeps_manual_key_not_issued_by_provider(): void {
		$order = $this->mockOrder( array( $this->record( 'aprovado', self::KEY ) ), self::MANUAL_KEY );
		$order->shouldNotReceive( 'update_meta_data' );

		$this->controller->applyInvoiceKey( $order );
	}

	public function test_replaces_cancelled_provider_key_with_reissued_one(): void {
		$order = $this->mockOrder(
			array(
				$this->record( 'cancelado', self::KEY ),
				$this->record( 'aprovado', self::REISSUED_KEY ),
			),
			self::KEY
		);
		$order->expects( 'update_meta_data' )->with( OrderInvoiceKeyMetaBoxController::META_KEY, self::REISSUED_KEY );
		$order->shouldNotReceive( 'save' );

		$this->controller->applyInvoiceKey( $order );
	}

	public function test_does_nothing_without_approved_key(): void {
		$order = $this->mockOrder( array( $this->record( 'processando', self::KEY ) ), '' );
		$order->shouldNotReceive( 'update_meta_data' );

		$this->controller->applyInvoiceKey( $order );
	}

	public function test_stores_key_provided_by_filter(): void {
		$order = $this->mockOrder( '', '' );
		Filters\expectApplied( InvoiceKeyResolverService::FILTER )->once()->andReturn( self::KEY );
		$order->expects( 'update_meta_data' )->with( OrderInvoiceKeyMetaBoxController::META_KEY, self::KEY );

		$this->controller->applyInvoiceKey( $order );
	}

	public function test_ignores_non_order_objects(): void {
		$refund = Mockery::mock( 'WC_Order_Refund' );
		$refund->shouldNotReceive( 'get_meta' );
		$refund->shouldNotReceive( 'update_meta_data' );

		$this->controller->applyInvoiceKey( $refund );
		$this->controller->applyInvoiceKey( null );

		$this->assertMockExpectationsMet();
	}

	private function record( string $status, string $key ): array {
		return array(
			'uuid'         => '6f1c1b5e-1f7b-4c1e-9a5d-2b7c3d4e5f60',
			'status'       => $status,
			'modelo'       => 'nfe',
			'data'         => '11/11/2026',
			'chave_acesso' => $key,
			'n_nfe'        => 123,
			'n_serie'      => 1,
			'url_xml'      => 'https://nfe.webmaniabr.com/xmlnfe/' . $key,
			'url_danfe'    => 'https://nfe.webmaniabr.com/danfe/' . $key,
		);
	}

	private function mockOrder( $nfeMeta, string $storedKey ) {
		$order = Mockery::mock( 'WC_Order' );
		$order->allows( 'get_meta' )->with( 'nfe', true )->andReturn( $nfeMeta );
		$order->allows( 'get_meta' )->with( OrderInvoiceKeyMetaBoxController::META_KEY, true )->andReturn( $storedKey );

		return $order;
	}
}
