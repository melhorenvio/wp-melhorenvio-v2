<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Providers;

use MelhorEnvio\Core\Container;
use MelhorEnvio\Database\Contracts\DatabaseInterface;
use MelhorEnvio\Database\Repositories\WordPressDatabaseRepository;
use MelhorEnvio\Hooks\HookManager;
use MelhorEnvio\Http\Controllers\Admin\AdminMenuController;
use MelhorEnvio\Http\Controllers\Admin\ModeNoticeController;
use MelhorEnvio\Http\Controllers\Auth\DisconnectController;
use MelhorEnvio\Http\Controllers\Auth\QuotationTokenController;
use MelhorEnvio\Http\Controllers\Auth\SaveSecretController;
use MelhorEnvio\Http\Controllers\Frontend\ProductShippingCalculatorController;
use MelhorEnvio\Http\Controllers\Order\NFeXmlUploadController;
use MelhorEnvio\Http\Controllers\Quotation\QuotationController;
use MelhorEnvio\Providers\CoreServiceProvider;
use MelhorEnvio\Services\Auth\SecretService;
use MelhorEnvio\Services\Auth\SignatureService;
use MelhorEnvio\Services\Quotation\MelhorEnvioApiClientService;
use MelhorEnvio\Services\Quotation\PostalCodeLocationClientService;
use MelhorEnvio\Services\Settings\IntegradorSettingsService;
use MelhorEnvio\Services\Shipping\CartItemsBuilderService;
use MelhorEnvio\Services\Shipping\ShippingZoneService;
use MelhorEnvio\Tests\TestCase;
use Mockery;

require_once dirname( __DIR__, 2 ) . '/Stubs/Extra/WordPressDatabaseRepositoryTest.php';

final class CoreServiceProviderTest extends TestCase {

	private Container $container;

	protected function setUp(): void {
		parent::setUp();
		$this->container = new Container();
	}

	protected function tearDown(): void {
		unset( $GLOBALS['wpdb'] );
		parent::tearDown();
	}

	/**
	 * @dataProvider singletonServices
	 */
	public function test_registers_service_as_singleton( string $abstract ): void {
		$this->registerProvider();

		$first = $this->container->get( $abstract );

		self::assertInstanceOf( $abstract, $first );
		self::assertSame( $first, $this->container->get( $abstract ) );
	}

	public function singletonServices(): array {
		$services = array(
			HookManager::class,
			AdminMenuController::class,
			IntegradorSettingsService::class,
			SecretService::class,
			SignatureService::class,
			SaveSecretController::class,
			QuotationController::class,
			CartItemsBuilderService::class,
			ProductShippingCalculatorController::class,
			MelhorEnvioApiClientService::class,
			PostalCodeLocationClientService::class,
			ShippingZoneService::class,
			QuotationTokenController::class,
			DisconnectController::class,
			ModeNoticeController::class,
			NFeXmlUploadController::class,
		);

		return array_combine(
			$services,
			array_map(
				static function ( string $service ): array {
					return array( $service );
				},
				$services
			)
		);
	}

	public function test_database_interface_resolves_to_repository_wrapping_global_wpdb(): void {
		$wpdb           = Mockery::mock( 'wpdb' );
		$wpdb->prefix   = 'wp_test_';
		$GLOBALS['wpdb'] = $wpdb;

		$this->registerProvider();
		$database = $this->container->get( DatabaseInterface::class );

		self::assertInstanceOf( WordPressDatabaseRepository::class, $database );
		self::assertSame( 'wp_test_orders', $database->getTableName( 'orders' ) );
		self::assertSame( $database, $this->container->get( DatabaseInterface::class ) );
	}

	private function registerProvider(): void {
		( new CoreServiceProvider( $this->container ) )->register();
	}
}
