<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Services\Settings;

use Brain\Monkey\Functions;
use MelhorEnvio\Services\Settings\IntegradorSettingsService;
use MelhorEnvio\Tests\TestCase;

final class IntegradorSettingsServiceTest extends TestCase {

	private const OPTION_KEY = 'melhor_envio_integrador_settings';

	private IntegradorSettingsService $service;

	protected function setUp(): void {
		parent::setUp();
		$this->service = new IntegradorSettingsService();
	}

	public function test_get_settings_returns_stored_settings(): void {
		$stored = array( 'calculator' => array( 'enabled' => true ) );
		$this->givenOptions( array( self::OPTION_KEY => $stored ) );

		self::assertSame( $stored, $this->service->getSettings() );
	}

	public function test_get_settings_returns_empty_array_when_nothing_is_stored(): void {
		$this->givenOptions( array() );

		self::assertSame( array(), $this->service->getSettings() );
	}

	/**
	 * @dataProvider unusableStoredValues
	 *
	 * @param mixed $stored
	 */
	public function test_get_settings_falls_back_to_legacy_when_stored_value_is_unusable( $stored ): void {
		$this->givenOptions(
			array(
				self::OPTION_KEY                              => $stored,
				'melhor_envio_option_where_show_calculator' => 'woocommerce_after_add_to_cart_form',
			)
		);

		self::assertSame(
			array(
				'calculator' => array(
					'enabled'  => true,
					'position' => 'woocommerce_after_add_to_cart_form',
				),
			),
			$this->service->getSettings()
		);
	}

	public function unusableStoredValues(): array {
		return array(
			'empty array' => array( array() ),
			'string'      => array( 'serialized' ),
			'empty str'   => array( '' ),
		);
	}

	/**
	 * @dataProvider hiddenCalculatorValues
	 *
	 * @param mixed $hidden
	 */
	public function test_legacy_hide_calculator_disables_calculator( $hidden ): void {
		$this->givenOptions( array( 'melhorenvio_hide_calculator_product' => $hidden ) );

		self::assertSame( array( 'calculator' => array( 'enabled' => false ) ), $this->service->getSettings() );
	}

	public function hiddenCalculatorValues(): array {
		return array(
			'string one' => array( '1' ),
			'int one'    => array( 1 ),
			'true'       => array( true ),
		);
	}

	/**
	 * @dataProvider visibleCalculatorValues
	 *
	 * @param mixed $hidden
	 */
	public function test_legacy_hide_calculator_keeps_calculator_enabled_for_other_values( $hidden ): void {
		$this->givenOptions( array( 'melhorenvio_hide_calculator_product' => $hidden ) );

		self::assertSame( array( 'calculator' => array( 'enabled' => true ) ), $this->service->getSettings() );
	}

	public function visibleCalculatorValues(): array {
		return array(
			'string zero'  => array( '0' ),
			'empty string' => array( '' ),
			'int zero'     => array( 0 ),
			'yes'          => array( 'yes' ),
		);
	}

	/**
	 * @dataProvider invalidPositions
	 *
	 * @param mixed $position
	 */
	public function test_legacy_position_is_ignored_when_not_a_non_empty_string( $position ): void {
		$this->givenOptions(
			array(
				'melhorenvio_hide_calculator_product'         => '0',
				'melhor_envio_option_where_show_calculator' => $position,
			)
		);

		self::assertSame( array( 'calculator' => array( 'enabled' => true ) ), $this->service->getSettings() );
	}

	public function invalidPositions(): array {
		return array(
			'empty string' => array( '' ),
			'array'        => array( array( 'x' ) ),
			'int'          => array( 1 ),
		);
	}

	public function test_legacy_dimensions_are_cast_to_float(): void {
		$this->givenOptions(
			array(
				'melhor_envio_option_dimension_default' => array(
					'height' => '10',
					'width'  => '20.5',
					'length' => 30,
					'weight' => '1,5',
				),
			)
		);

		self::assertSame(
			array(
				'calculator'         => array( 'enabled' => true ),
				'dimensions_default' => array(
					'height' => 10.0,
					'width'  => 20.5,
					'length' => 30.0,
					'weight' => 1.0,
				),
			),
			$this->service->getSettings()
		);
	}

	public function test_legacy_dimensions_keep_only_known_keys_present(): void {
		$this->givenOptions(
			array(
				'melhor_envio_option_dimension_default' => array(
					'width'   => '5',
					'unknown' => '99',
				),
			)
		);

		self::assertSame(
			array( 'width' => 5.0 ),
			$this->service->getSettings()['dimensions_default']
		);
	}

	/**
	 * @dataProvider invalidDimensions
	 *
	 * @param mixed $dimensions
	 */
	public function test_legacy_dimensions_are_ignored_when_not_a_non_empty_array( $dimensions ): void {
		$this->givenOptions( array( 'melhor_envio_option_dimension_default' => $dimensions ) );

		self::assertArrayNotHasKey( 'dimensions_default', $this->service->getSettings() );
	}

	public function invalidDimensions(): array {
		return array(
			'empty array' => array( array() ),
			'string'      => array( '10x20x30' ),
		);
	}

	public function test_save_settings_returns_true_when_option_is_updated(): void {
		$settings = array( 'checkout' => array( 'a' => 1 ) );

		Functions\expect( 'update_option' )->once()->with( self::OPTION_KEY, $settings )->andReturn( true );

		self::assertTrue( $this->service->saveSettings( $settings ) );
	}

	public function test_save_settings_returns_true_when_value_is_unchanged(): void {
		$settings = array( 'checkout' => array( 'a' => 1 ) );

		Functions\expect( 'update_option' )->once()->andReturn( false );
		$this->givenOptions( array( self::OPTION_KEY => $settings ) );

		self::assertTrue( $this->service->saveSettings( $settings ) );
	}

	public function test_save_settings_returns_false_when_update_fails(): void {
		Functions\expect( 'update_option' )->once()->andReturn( false );
		$this->givenOptions( array( self::OPTION_KEY => array( 'old' => true ) ) );

		self::assertFalse( $this->service->saveSettings( array( 'new' => true ) ) );
	}

	public function test_get_checkout_settings_returns_checkout_section(): void {
		$this->givenOptions( array( self::OPTION_KEY => array( 'checkout' => array( 'show_delivery_time' => true ) ) ) );

		self::assertSame( array( 'show_delivery_time' => true ), $this->service->getCheckoutSettings() );
	}

	public function test_get_checkout_settings_returns_empty_array_when_section_missing(): void {
		$this->givenOptions( array( self::OPTION_KEY => array( 'calculator' => array( 'enabled' => true ) ) ) );

		self::assertSame( array(), $this->service->getCheckoutSettings() );
	}

	/**
	 * @param array<string, mixed> $options
	 */
	private function givenOptions( array $options ): void {
		Functions\when( 'get_option' )->alias(
			static function ( $key, $default = false ) use ( $options ) {
				return array_key_exists( $key, $options ) ? $options[ $key ] : $default;
			}
		);
	}
}
