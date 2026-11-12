<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Services\Order;

use Brain\Monkey\Filters;
use MelhorEnvio\Services\Order\InvoiceKeyResolverService;
use MelhorEnvio\Tests\TestCase;
use Mockery;

final class InvoiceKeyResolverServiceTest extends TestCase {

	private const KEY          = '35240612345678000190550010000001231234567890';
	private const REISSUED_KEY = '35240612345678000190550010000001241234567891';

	private InvoiceKeyResolverService $resolver;

	protected function setUp(): void {
		parent::setUp();
		$this->resolver = new InvoiceKeyResolverService();
	}

	public function test_resolves_key_of_approved_nfe(): void {
		$order = $this->orderWith( array( $this->record( 'aprovado', self::KEY ) ) );

		self::assertSame( self::KEY, $this->resolver->resolve( $order ) );
	}

	/**
	 * @dataProvider recordsWithoutUsableKey
	 */
	public function test_ignores_records_without_usable_key( array $record ): void {
		$order = $this->orderWith( array( $record ) );

		self::assertNull( $this->resolver->resolve( $order ) );
	}

	public function recordsWithoutUsableKey(): array {
		return array(
			'processando'           => array( $this->record( 'processando', self::KEY ) ),
			'cancelado'             => array( $this->record( 'cancelado', self::KEY ) ),
			'reprovado'             => array( $this->record( 'reprovado', self::KEY ) ),
			'nfse'                  => array( array( 'modelo' => 'nfse' ) + $this->record( 'aprovado', self::KEY ) ),
			'lote_rps'              => array( array( 'modelo' => 'lote_rps' ) + $this->record( 'aprovado', self::KEY ) ),
			'alphanumeric cnpj'     => array( $this->record( 'aprovado', '3524061234AB78000190550010000001231234567890' ) ),
			'model 65 (NFC-e)'      => array( $this->record( 'aprovado', '35240612345678000190650010000001231234567890' ) ),
			'missing chave_acesso'  => array( array_diff_key( $this->record( 'aprovado', self::KEY ), array( 'chave_acesso' => true ) ) ),
			'non string chave'      => array( array( 'chave_acesso' => array( self::KEY ) ) + $this->record( 'aprovado', self::KEY ) ),
		);
	}

	public function test_record_without_modelo_counts_as_nfe(): void {
		$record = $this->record( 'aprovado', self::KEY );
		unset( $record['modelo'] );

		self::assertSame( self::KEY, $this->resolver->resolve( $this->orderWith( array( $record ) ) ) );
	}

	public function test_last_approved_record_wins(): void {
		$order = $this->orderWith(
			array(
				$this->record( 'aprovado', self::KEY ),
				$this->record( 'aprovado', self::REISSUED_KEY ),
				$this->record( 'processando', '35240612345678000190550010000001251234567892' ),
				array( 'modelo' => 'nfse' ) + $this->record( 'aprovado', '35240612345678000190550010000001261234567893' ),
			)
		);

		self::assertSame( self::REISSUED_KEY, $this->resolver->resolve( $order ) );
	}

	public function test_normalizes_masked_key(): void {
		$order = $this->orderWith( array( $this->record( 'aprovado', '3524 0612 3456 7800 0190.5500.1000.0001.2312.3456.7890' ) ) );

		self::assertSame( self::KEY, $this->resolver->resolve( $order ) );
	}

	/**
	 * @dataProvider malformedMeta
	 */
	public function test_ignores_malformed_meta( $meta ): void {
		$order = $this->orderWith( $meta );

		self::assertNull( $this->resolver->resolve( $order ) );
		self::assertSame( array(), $this->resolver->providerKeys( $order ) );
	}

	public function malformedMeta(): array {
		return array(
			'missing'          => array( '' ),
			'string'           => array( self::KEY ),
			'list of strings'  => array( array( self::KEY ) ),
			'record not array' => array( array( 'status' => 'aprovado', 'chave_acesso' => self::KEY ) ),
		);
	}

	public function test_filter_provides_key_without_webmaniabr(): void {
		$order = $this->orderWith( '' );

		Filters\expectApplied( InvoiceKeyResolverService::FILTER )->once()->with( null, $order )->andReturn( self::KEY );

		self::assertSame( self::KEY, $this->resolver->resolve( $order ) );
	}

	public function test_filter_receives_webmaniabr_key(): void {
		$order = $this->orderWith( array( $this->record( 'aprovado', self::KEY ) ) );

		Filters\expectApplied( InvoiceKeyResolverService::FILTER )->once()->with( self::KEY, $order )->andReturn( self::REISSUED_KEY );

		self::assertSame( self::REISSUED_KEY, $this->resolver->resolve( $order ) );
	}

	/**
	 * @dataProvider invalidFilterReturns
	 */
	public function test_ignores_invalid_filter_return( $filtered ): void {
		$order = $this->orderWith( array( $this->record( 'aprovado', self::KEY ) ) );

		Filters\expectApplied( InvoiceKeyResolverService::FILTER )->once()->andReturn( $filtered );

		self::assertNull( $this->resolver->resolve( $order ) );
	}

	public function invalidFilterReturns(): array {
		return array(
			'false'           => array( false ),
			'integer'         => array( 123 ),
			'masked'          => array( '3524 0612 3456 7800 0190 5500 1000 0001 2312 3456 7890' ),
			'model 65'        => array( '35240612345678000190650010000001231234567890' ),
			'trailing break'  => array( self::KEY . "\n" ),
		);
	}

	public function test_provider_keys_lists_every_nfe_key(): void {
		$order = $this->orderWith(
			array(
				$this->record( 'cancelado', self::KEY ),
				$this->record( 'aprovado', self::REISSUED_KEY ),
				array( 'modelo' => 'nfse' ) + $this->record( 'aprovado', '35240612345678000190550010000001261234567893' ),
			)
		);

		self::assertSame( array( self::KEY, self::REISSUED_KEY ), $this->resolver->providerKeys( $order ) );
	}

	private function record( string $status, string $key ): array {
		return array(
			'uuid'                   => '6f1c1b5e-1f7b-4c1e-9a5d-2b7c3d4e5f60',
			'status'                 => $status,
			'modelo'                 => 'nfe',
			'data'                   => '11/11/2026',
			'chave_acesso'           => $key,
			'n_nfe'                  => 123,
			'n_serie'                => 1,
			'n_recibo'               => 351000000000001,
			'url_xml'                => 'https://nfe.webmaniabr.com/xmlnfe/' . $key,
			'url_danfe'              => 'https://nfe.webmaniabr.com/danfe/' . $key,
			'url_danfe_simplificada' => 'https://nfe.webmaniabr.com/danfe/simplificada/' . $key,
			'url_danfe_etiqueta'     => 'https://nfe.webmaniabr.com/danfe/etiqueta/' . $key,
			'nfe_doc'                => '',
			'motivo'                 => '',
		);
	}

	private function orderWith( $meta ) {
		$order = Mockery::mock( 'WC_Order' );
		$order->allows( 'get_meta' )->with( 'nfe', true )->andReturn( $meta );

		return $order;
	}
}
