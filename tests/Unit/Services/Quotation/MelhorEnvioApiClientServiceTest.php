<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Services\Quotation;

use Brain\Monkey\Functions;
use MelhorEnvio\Services\Quotation\MelhorEnvioApiClientService;
use MelhorEnvio\Tests\TestCase;
use Mockery;
use WP_Error;

final class MelhorEnvioApiClientServiceTest extends TestCase {

	private const TOKEN_OPTION   = 'melhor_envio_integrador_quotation_token';
	private const API_URL_OPTION = 'melhor_envio_integrador_me_api_url';
	private const LOG_CONTEXT    = array( 'source' => 'melhor-envio-cotacao' );

	private MelhorEnvioApiClientService $service;

	/** @var \Mockery\MockInterface */
	private $logger;

	/** @var array<string, mixed> */
	private array $options = array();

	protected function setUp(): void {
		parent::setUp();

		$this->service = new MelhorEnvioApiClientService();
		$this->logger  = Mockery::mock( 'WC_Logger' );
		$this->logger->allows( 'debug' );

		$this->options = array( self::TOKEN_OPTION => 'token-123' );

		Functions\when( 'wc_get_logger' )->justReturn( $this->logger );
		Functions\when( 'get_option' )->alias(
			function ( $name, $default = false ) {
				return array_key_exists( $name, $this->options ) ? $this->options[ $name ] : $default;
			}
		);
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
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

	public function test_returns_empty_and_warns_when_token_is_missing(): void {
		$this->options = array();

		$this->logger->expects( 'warning' )->with( Mockery::pattern( '/token/' ), self::LOG_CONTEXT );
		Functions\expect( 'wp_remote_post' )->never();

		self::assertSame( array(), $this->service->getQuotations( '01001000', '20040002', $this->items() ) );
	}

	/**
	 * @dataProvider invalidPostalCodes
	 */
	public function test_returns_empty_and_warns_when_postal_code_is_invalid( string $from, string $to ): void {
		$this->logger->expects( 'warning' )->with( Mockery::pattern( '/CEP inválido/' ), self::LOG_CONTEXT );
		Functions\expect( 'wp_remote_post' )->never();

		self::assertSame( array(), $this->service->getQuotations( $from, $to, $this->items() ) );
	}

	public function invalidPostalCodes(): array {
		return array(
			'short origin'      => array( '0100100', '20040002' ),
			'long destination'  => array( '01001000', '200400021' ),
			'empty destination' => array( '01001000', '' ),
			'letters only'      => array( 'abcdefgh', '20040002' ),
		);
	}

	public function test_sends_quotation_request_to_configured_api(): void {
		$this->options[ self::API_URL_OPTION ] = 'https://sandbox.melhorenvio.com.br/';

		$captured = array();
		Functions\expect( 'wp_remote_post' )
			->once()
			->andReturnUsing(
				static function ( string $url, array $args ) use ( &$captured ): array {
					$captured = array( $url, $args );
					return array(
						'response' => array( 'code' => 200 ),
						'body'     => '[]',
					);
				}
			);

		$this->service->getQuotations( '01001-000', '20040-002', $this->items() );

		list( $url, $args ) = $captured;

		self::assertSame( 'https://sandbox.melhorenvio.com.br/api/v2/me/shipment/calculate', $url );
		self::assertSame(
			array(
				'Authorization'     => 'Bearer token-123',
				'Content-Type'      => 'application/json',
				'Accept'            => 'application/json',
				'version-plugin-me' => defined( 'MELHORENVIO_VERSION' ) ? MELHORENVIO_VERSION : '',
			),
			$args['headers']
		);
		self::assertSame( 15, $args['timeout'] );
		self::assertFalse( $args['sslverify'] );
		self::assertSame(
			array(
				'from'                => array( 'postal_code' => '01001000' ),
				'to'                  => array( 'postal_code' => '20040002' ),
				'products'            => $this->items(),
				'custom_presentation' => true,
			),
			json_decode( $args['body'], true )
		);
	}

	public function test_uses_production_api_url_by_default(): void {
		Functions\expect( 'wp_remote_post' )
			->once()
			->with( 'https://melhorenvio.com.br/api/v2/me/shipment/calculate', Mockery::type( 'array' ) )
			->andReturn(
				array(
					'response' => array( 'code' => 200 ),
					'body'     => '[]',
				)
			);

		self::assertSame( array(), $this->service->getQuotations( '01001000', '20040002', $this->items() ) );
	}

	public function test_returns_only_services_with_price_and_without_error(): void {
		$body = array(
			array(
				'id'    => 1,
				'name'  => 'PAC',
				'price' => '20.50',
			),
			array(
				'id'    => 2,
				'name'  => 'SEDEX',
				'error' => 'Transportadora não atende este trecho.',
			),
			array(
				'id'   => 3,
				'name' => 'Sem preço',
			),
			'not-an-array',
			array(
				'id'    => 4,
				'name'  => 'Jadlog',
				'price' => '31.00',
				'error' => '',
			),
		);

		$this->givenResponse( 200, (string) json_encode( $body ) );

		$quotations = $this->service->getQuotations( '01001000', '20040002', $this->items() );

		self::assertSame( array( $body[0], $body[4] ), array_values( $quotations ) );
	}

	public function test_returns_empty_and_logs_error_on_transport_failure(): void {
		Functions\when( 'wp_remote_post' )->justReturn( new WP_Error( 'http_request_failed', 'cURL error 28' ) );

		$this->logger->expects( 'error' )->with( Mockery::pattern( '/cURL error 28/' ), self::LOG_CONTEXT );

		self::assertSame( array(), $this->service->getQuotations( '01001000', '20040002', $this->items() ) );
	}

	/**
	 * @dataProvider nonSuccessStatusCodes
	 */
	public function test_returns_empty_and_logs_error_on_non_200_status( int $status ): void {
		$this->givenResponse( $status, '{"message":"Unauthenticated."}' );

		$this->logger->expects( 'error' )->with( Mockery::pattern( "/HTTP {$status}.*Unauthenticated/" ), self::LOG_CONTEXT );

		self::assertSame( array(), $this->service->getQuotations( '01001000', '20040002', $this->items() ) );
	}

	public function nonSuccessStatusCodes(): array {
		return array(
			'created'       => array( 201 ),
			'unauthorized'  => array( 401 ),
			'unprocessable' => array( 422 ),
			'server error'  => array( 500 ),
		);
	}

	/**
	 * @dataProvider invalidJsonBodies
	 */
	public function test_returns_empty_and_logs_error_on_invalid_json( string $body ): void {
		$this->givenResponse( 200, $body );

		$this->logger->expects( 'error' )->with( 'Cotação retornou JSON inválido.', self::LOG_CONTEXT );

		self::assertSame( array(), $this->service->getQuotations( '01001000', '20040002', $this->items() ) );
	}

	public function invalidJsonBodies(): array {
		return array(
			'malformed' => array( '{"price":' ),
			'html'      => array( '<html>Bad gateway</html>' ),
			'scalar'    => array( '"ok"' ),
			'empty'     => array( '' ),
		);
	}

	private function givenResponse( int $status, string $body ): void {
		Functions\when( 'wp_remote_post' )->justReturn(
			array(
				'response' => array( 'code' => $status ),
				'body'     => $body,
			)
		);
	}

	private function items(): array {
		return array(
			array(
				'id'              => 10,
				'weight'          => 0.5,
				'width'           => 11.5,
				'height'          => 2.5,
				'length'          => 16.5,
				'quantity'        => 1,
				'insurance_value' => 49.9,
			),
		);
	}
}
