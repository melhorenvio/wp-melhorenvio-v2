<?php

if ( ! function_exists( 'update_option' ) ) {
	die( "Error: WordPress not loaded. Run via WP-CLI: wp eval-file wp-content/plugins/melhor-envio-cotacao/bin/seed.php\n" );
}

$autoload = __DIR__ . '/../vendor/autoload.php';
if ( ! file_exists( $autoload ) ) {
	die( "Error: Composer autoload not found. Run 'composer install'.\n" );
}

require_once $autoload;

use MelhorEnvio\Database\Seeders\DatabaseSeeder;

try {
	echo "Running seeders...\n\n";

	$seeder = new DatabaseSeeder();
	$seeder->run();

	echo "Seeders completed successfully!\n";
} catch ( \Exception $e ) {
	echo 'Error: ' . $e->getMessage() . "\n";
	exit( 1 );
}
