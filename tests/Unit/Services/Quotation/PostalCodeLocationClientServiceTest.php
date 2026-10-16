<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Services\Quotation;

use Brain\Monkey\Functions;
use MelhorEnvio\Services\Quotation\PostalCodeLocationClientService;
use MelhorEnvio\Tests\TestCase;
use Mockery;
use WP_Error;

final class PostalCodeLocationClientServiceTest extends TestCase {

	private const ME_URL     = 'https://location.melhorenvio.com/01001000';
	private const VIACEP_URL = 'https://viacep.com.br/ws/01001000/json';

	private PostalCodeLocationClientService $service;

	/** @var \Mockery\MockInterface */
	private $logger;

	/** @var array<string, mixed> Response per requested URL. */
	private array $responses = array();

	/** @var array<int, array{0: string, 1: array}> */
	private array $requests = array();

	protected function setUp(): void {
		parent::setUp();

		$this->service = new PostalCodeLocationClientService();
		$this->logger  = Mockery::mock( 'WC_Logger' );

		Functions\when( 'wc_get_logger' )->justReturn( $this->logger );
		Functions\when( 'wp_remote_get' )->alias(
			function ( string $url, array $args = array() ) {
				$this->requests[] = array( $url, $args );

				if ( ! array_key_exists( $url, $this->responses ) ) {
					throw new \LogicException( "Unexpected request to {$url}." );
				}

				return $this->responses[ $url ];
			}
		);
		Functions\when( 'is_wp_error' )->alias(
			static function ( $thing ): bool {
				return $thing instanceof WP_Error;
			}
		);
		Functions\when( 'wp_remote_retrieve_response_code' )->alias(
			static function ( $response ) {
				return $response['response']['code'] ?? '';
			}
		);
		Functions\when( 'wp_remote_retrieve_body' )->alias(
			static function ( $response ): string {
				return $response['body'] ?? '';
			}
		);
	}

	/**
	 * @dataProvider invalidPostalCodes
	 */
	public function test_returns_null_without_requests_for_invalid_postal_code( string $cep ): void {
		self::assertNull( $this->service->getState( $cep ) );
		self::assertSame( array(), $this->requests );
	}

	public function invalidPostalCodes(): array {
		return array(
			'empty'     => array( '' ),
			'too short' => array( '0100100' ),
			'too long'  => array( '010010001' ),
			'letters'   => array( 'abcdefgh' ),
		);
	}

	public function test_returns_state_from_melhor_envio_location_api(): void {
		$this->responses[ self::ME_URL ] = $this->jsonResponse( 200, array( 'uf' => 'SP' ) );

		self::assertSame( 'SP', $this->service->getState( '01001-000' ) );
		self::assertCount( 1, $this->requests );

		list( $url, $args ) = $this->requests[0];
		self::assertSame( self::ME_URL, $url );
		self::assertSame( 5, $args['timeout'] );
		self::assertSame(
			array( 'version-plugin-me' => defined( 'MELHORENVIO_VERSION' ) ? MELHORENVIO_VERSION : '' ),
			$args['headers']
		);
	}

	public function test_falls_back_to_viacep_when_melhor_envio_reports_error(): void {
		$this->responses[ self::ME_URL ]     = $this->jsonResponse(
			200,
			array(
				'message' => 'CEP não encontrado',
				'uf'      => 'XX',
			)
		);
		$this->responses[ self::VIACEP_URL ] = $this->jsonResponse( 200, array( 'uf' => 'SP' ) );

		self::assertSame( 'SP', $this->service->getState( '01001000' ) );
		self::assertSame( array( self::ME_URL, self::VIACEP_URL ), array_column( $this->requests, 0 ) );
	}

	public function test_falls_back_to_viacep_and_logs_warning_on_transport_error(): void {
		$this->responses[ self::ME_URL ]     = new WP_Error( 'http_request_failed', 'timeout' );
		$this->responses[ self::VIACEP_URL ] = $this->jsonResponse( 200, array( 'uf' => 'RJ' ) );

		$this->logger->expects( 'warning' )
			->with( 'Falha ao consultar ' . self::ME_URL . ': timeout', array( 'source' => 'melhor-envio-cotacao' ) );

		self::assertSame( 'RJ', $this->service->getState( '01001000' ) );
	}

	/**
	 * @dataProvider unusableMelhorEnvioResponses
	 *
	 * @param array<string, mixed> $response
	 */
	public function test_falls_back_to_viacep_when_melhor_envio_response_is_unusable( array $response ): void {
		$this->responses[ self::ME_URL ]     = $response;
		$this->responses[ self::VIACEP_URL ] = $this->jsonResponse( 200, array( 'uf' => 'MG' ) );

		self::assertSame( 'MG', $this->service->getState( '01001000' ) );
	}

	public function unusableMelhorEnvioResponses(): array {
		return array(
			'non 200 status' => array( $this->jsonResponse( 500, array( 'uf' => 'SP' ) ) ),
			'invalid json'   => array(
				array(
					'response' => array( 'code' => 200 ),
					'body'     => '<html>',
				),
			),
			'missing uf'     => array( $this->jsonResponse( 200, array( 'city' => 'São Paulo' ) ) ),
			'empty uf'       => array( $this->jsonResponse( 200, array( 'uf' => '' ) ) ),
		);
	}

	public function test_returns_null_when_viacep_reports_error(): void {
		$this->responses[ self::ME_URL ]     = $this->jsonResponse( 404, array() );
		$this->responses[ self::VIACEP_URL ] = $this->jsonResponse( 200, array( 'erro' => true ) );

		self::assertNull( $this->service->getState( '01001000' ) );
	}

	public function test_returns_null_when_both_sources_fail(): void {
		$this->responses[ self::ME_URL ]     = new WP_Error( 'http_request_failed', 'down' );
		$this->responses[ self::VIACEP_URL ] = new WP_Error( 'http_request_failed', 'down' );

		$this->logger->expects( 'warning' )->twice();

		self::assertNull( $this->service->getState( '01001000' ) );
	}

	private function jsonResponse( int $status, array $body ): array {
		return array(
			'response' => array( 'code' => $status ),
			'body'     => (string) json_encode( $body ),
		);
	}
}
