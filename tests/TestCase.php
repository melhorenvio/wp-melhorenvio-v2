<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests;

use Brain\Monkey;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Base class for unit tests. WordPress/WooCommerce functions are mocked via Brain Monkey;
 * WP/WC classes received as arguments (WC_Order, WP_Comment...) are mocked via Mockery.
 * WP/WC classes that src/ instantiates, extends or calls statically have fakes in tests/Stubs.
 */
abstract class TestCase extends PHPUnitTestCase {

	use MockeryPHPUnitIntegration;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		\WooCommerceFakes::reset();
		$_POST = array();
		$_GET  = array();
		parent::tearDown();
	}
}
