<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Support;

use Brain\Monkey\Functions;
use MelhorEnvio\Support\UnitConverter;
use MelhorEnvio\Tests\TestCase;

final class UnitConverterTest extends TestCase {

	public function test_to_kg_converts_from_store_weight_unit(): void {
		Functions\expect( 'get_option' )->once()->with( 'woocommerce_weight_unit', 'kg' )->andReturn( 'g' );
		Functions\expect( 'wc_get_weight' )->once()->with( 1500.0, 'kg', 'g' )->andReturn( 1.5 );

		self::assertSame( 1.5, UnitConverter::toKg( 1500.0 ) );
	}

	public function test_to_kg_lowercases_store_unit(): void {
		Functions\when( 'get_option' )->justReturn( 'LBS' );
		Functions\expect( 'wc_get_weight' )->once()->with( 2.0, 'kg', 'lbs' )->andReturn( 0.907 );

		self::assertSame( 0.907, UnitConverter::toKg( 2.0 ) );
	}

	public function test_to_kg_casts_result_to_float(): void {
		Functions\when( 'get_option' )->justReturn( 'kg' );
		Functions\when( 'wc_get_weight' )->justReturn( '3' );

		self::assertSame( 3.0, UnitConverter::toKg( 3.0 ) );
	}

	public function test_to_cm_converts_from_store_dimension_unit(): void {
		Functions\expect( 'get_option' )->once()->with( 'woocommerce_dimension_unit', 'cm' )->andReturn( 'mm' );
		Functions\expect( 'wc_get_dimension' )->once()->with( 100.0, 'cm', 'mm' )->andReturn( 10.0 );

		self::assertSame( 10.0, UnitConverter::toCm( 100.0 ) );
	}

	public function test_to_cm_lowercases_store_unit_and_casts_result(): void {
		Functions\when( 'get_option' )->justReturn( 'IN' );
		Functions\expect( 'wc_get_dimension' )->once()->with( 1.0, 'cm', 'in' )->andReturn( '2.54' );

		self::assertSame( 2.54, UnitConverter::toCm( 1.0 ) );
	}
}
