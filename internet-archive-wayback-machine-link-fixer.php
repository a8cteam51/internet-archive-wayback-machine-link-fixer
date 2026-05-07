<?php
/**
 * The wayback-link-fixer bootstrap file.
 *
 * @since       1.0.0
 * @version     1.4.1
 * @author       Internet Archive
 * @license     GPL-3.0-or-later
 *
 * @noinspection ALL
 *
 * @wordpress-plugin
 * Plugin Name:             Internet Archive Wayback Machine Link Fixer
 * Description:             This plugin scans your content for links, replacing broken ones with archived versions from the Wayback Machine. It also features Auto Archiving, which automatically creates snapshots of your own pages and any other links on your site that aren’t yet archived, ensuring long-term accessibility.
 * Version:                 1.4.1
 * Requires at least:       6.4
 * Tested up to:            7.0
 * Requires PHP:            7.4
 * Author:                  Internet Archive
 * Author URI:              https://archive.org
 * License:                 GPL-3.0-or-later
 * License URI:             https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:             internet-archive-wayback-machine-link-fixer
 * Domain Path:             /languages
 */

defined( 'ABSPATH' ) || exit;

// Define plugin constants.
define( 'IAWMLF_BASENAME', plugin_basename( __FILE__ ) );
define( 'IAWMLF_PATH', plugin_dir_path( __FILE__ ) );
define( 'IAWMLF_URL', plugin_dir_url( __FILE__ ) );
define( 'IAWMLF_VERSION', '1.4.1' );
define(
	'IAWMLF_MINIMUM_VERSIONS',
	array(
		'wp'  => '6.4',
		'php' => '7.4',
	)
);

// Load the rest of the bootstrap functions.
require_once IAWMLF_PATH . '/functions-bootstrap.php';

// Declare compatibility with WC features.
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

// Load the autoloader.
if ( ! is_file( IAWMLF_PATH . '/vendor/autoload.php' ) ) {
	iawmlf_output_requirements_error( new WP_Error( 'missing_autoloader' ) );
	return;
}
require_once IAWMLF_PATH . '/vendor/autoload.php';

define( 'IAWMLF_MINIMUM_REQUIREMENTS', iawmlf_validate_requirements() );

if ( is_wp_error( IAWMLF_MINIMUM_REQUIREMENTS ) ) {
	iawmlf_output_requirements_error( IAWMLF_MINIMUM_REQUIREMENTS );
} else {
	// Include the action scheduler integration.
	require_once IAWMLF_PATH . 'vendor/woocommerce/action-scheduler/action-scheduler.php';

	// Add all migrations.
	\Internet_Archive\Wayback_Machine_Link_Fixer\Migration\Migrations::$migrations = array( //phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
		\Internet_Archive\Wayback_Machine_Link_Fixer_Migration\Migration_1::class,
	);

	require_once IAWMLF_PATH . 'functions.php';
	add_action( 'plugins_loaded', array( iawmlf_get_plugin_instance(), 'maybe_initialize' ) );

	register_activation_hook( __FILE__, 'iawmlf_activate' );
	register_uninstall_hook( __FILE__, 'iawmlf_uninstall' );
}
