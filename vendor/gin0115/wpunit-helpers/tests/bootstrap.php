<?php

/**
 * PHPUnit bootstrap file
 */

use Gin0115\WPUnit_Helpers\WP\WP_Dependencies;

// Composer autoloader must be loaded before WP_PHPUNIT__DIR will be available
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Give access to tests_add_filter() function.
require_once getenv( 'WP_PHPUNIT__DIR' ) . '/includes/functions.php';

// Load all environment variables into $_ENV
try {
	$dotenv = Dotenv\Dotenv::createUnsafeImmutable( __DIR__ );
	$dotenv->load();
} catch (\Throwable $th) {
	// Do nothing if fails to find env as not used in pipeline.
	die('Failed to load .env file');
}

// Set the fixtures path as a const.
define( 'TEST_FIXTURES_PATH', __DIR__ . '/Fixtures/' );
define( 'TEST_WP_INSTANCE_PATH', dirname( __DIR__, 1 ) . '/wordpress' );

tests_add_filter(
	'muplugins_loaded',
	function() {
		
		// Download WooCommerce (For all WC traits and factories)
		WP_Dependencies::install_remote_plugin_from_zip(
			'https://downloads.wordpress.org/plugin/woocommerce.4.9.2.zip',
			TEST_WP_INSTANCE_PATH
		);
		WP_Dependencies::activate_plugin( 'woocommerce/woocommerce.php' );
	}
);

// Start up the WP testing environment.
require getenv( 'WP_PHPUNIT__DIR' ) . '/includes/bootstrap.php';
