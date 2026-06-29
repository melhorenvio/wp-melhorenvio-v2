<?php

declare(strict_types=1);

namespace MelhorEnvio\Infrastructure\WordPress\Seeder;

final class DatabaseSeeder {

	private array $seeders = array();

	public function __construct() {
		$this->registerSeeders();
	}

	private function registerSeeders(): void {
		$this->seeders = array(
			WooCommerceSeeder::class,
		);
	}

	public function run(): void {
		foreach ( $this->seeders as $seederClass ) {
			if ( ! class_exists( $seederClass ) ) {
				continue;
			}

			if ( ! is_subclass_of( $seederClass, SeederInterface::class ) ) {
				continue;
			}

			$seeder = new $seederClass();
			$seeder->run();
		}
	}

	public function addSeeder( string $seederClass ): void {
		if ( ! is_subclass_of( $seederClass, SeederInterface::class ) ) {
			throw new \InvalidArgumentException(
				sprintf( 'Seeder %s must implement %s', $seederClass, SeederInterface::class )
			);
		}

		$this->seeders[] = $seederClass;
	}
}
