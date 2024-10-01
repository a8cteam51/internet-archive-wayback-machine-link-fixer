<?php
/**
 * PHPUnit bootstrap file
 */

// Composer autoloader must be loaded before WP_PHPUNIT__DIR will be available
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Give access to tests_add_filter() function.
require_once getenv( 'WP_PHPUNIT__DIR' ) . '/includes/functions.php';

// Load all environment variables into $_ENV
try {
	$dotenv = Dotenv\Dotenv::createUnsafeImmutable( __DIR__ );
	$dotenv->load();
} catch ( \Throwable $th ) {
	// Do nothing if fails to find env as not used in pipeline.
}

define( 'FIXTURES_PATH', __DIR__ . '/Fixtures' );


tests_add_filter(
	'muplugins_loaded',
	function () {
		// Activate the plugin.
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		activate_plugin( 'wayback-link-fixer/wayback-link-fixer.php' );

		// include the function file
		include_once dirname( __DIR__ ) . '/functions.php';
	}
);

// Start up the WP testing environment.
require getenv( 'WP_PHPUNIT__DIR' ) . '/includes/bootstrap.php';
