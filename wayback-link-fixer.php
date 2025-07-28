<?php
/**
 * The wayback-link-fixer bootstrap file.
 *
 * @since       1.0.0
 * @version     1.3.0
 * @author      WordPress.com Special Projects
 * @license     GPL-3.0-or-later
 *
 * @noinspection ALL
 *
 * @wordpress-plugin
 * Plugin Name:             Internet Archive Wayback Machine Link Fixer
 * Plugin URI:              https://wpspecialprojects.wordpress.com
 * Description:             This plugin scans your content for links, replacing broken ones with archived versions from the Wayback Machine. It also features Auto Archiving, which automatically creates snapshots of your own pages and any other links on your site that aren’t yet archived, ensuring long-term accessibility.
 * Version:                 1.3.0
 * Requires at least:       6.4
 * Tested up to:            6.5
 * Requires PHP:            7.4
 * Author:                  WordPress.com Special Projects
 * Author URI:              https://wpspecialprojects.wordpress.com
 * License:                 GPL-3.0-or-later
 * License URI:             https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:             wayback-link-fixer
 * Domain Path:             /languages
 */

defined( 'ABSPATH' ) || exit;

// Define plugin constants.
function_exists( 'get_plugin_data' ) || require_once ABSPATH . 'wp-admin/includes/plugin.php';
define( 'WPCOMSP_WAYBACK_LINK_FIXER_METADATA', get_plugin_data( __FILE__, false, false ) );

define( 'WPCOMSP_WAYBACK_LINK_FIXER_BASENAME', plugin_basename( __FILE__ ) );
define( 'WPCOMSP_WAYBACK_LINK_FIXER_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPCOMSP_WAYBACK_LINK_FIXER_URL', plugin_dir_url( __FILE__ ) );

// Load the rest of the bootstrap functions.
require_once WPCOMSP_WAYBACK_LINK_FIXER_PATH . '/functions-bootstrap.php';

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
if ( ! is_file( WPCOMSP_WAYBACK_LINK_FIXER_PATH . '/vendor/autoload.php' ) ) {
	wpcomsp_wayback_link_fixer_output_requirements_error( new WP_Error( 'missing_autoloader' ) );
	return;
}
require_once WPCOMSP_WAYBACK_LINK_FIXER_PATH . '/vendor/autoload.php';

define( 'WPCOMSP_WAYBACK_LINK_FIXER_REQUIREMENTS', wpcomsp_wayback_link_fixer_validate_requirements() );

if ( is_wp_error( WPCOMSP_WAYBACK_LINK_FIXER_REQUIREMENTS ) ) {
	wpcomsp_wayback_link_fixer_output_requirements_error( WPCOMSP_WAYBACK_LINK_FIXER_REQUIREMENTS );
} else {
	// Include the action scheduler integration.
	require_once WPCOMSP_WAYBACK_LINK_FIXER_PATH . 'lib/action-scheduler/action-scheduler.php';

	// Add all migrations.
	\WPCOMSpecialProjects\Wayback_Link_Fixer\Migration\Migrations::$migrations = array( //phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
		\WPCOMSpecialProjects\Wayback_Link_Fixer_Migration\Migration_1::class,
		\WPCOMSpecialProjects\Wayback_Link_Fixer_Migration\Migration_2::class,
		\WPCOMSpecialProjects\Wayback_Link_Fixer_Migration\Migration_3::class,
	);

	add_action( 'wp_head', 'wpcomsp_wayback_link_fixer_render_archived_link_css', 999 );


	require_once WPCOMSP_WAYBACK_LINK_FIXER_PATH . 'functions.php';
	add_action( 'plugins_loaded', array( wpcomsp_wayback_link_fixer_get_plugin_instance(), 'maybe_initialize' ) );

	register_activation_hook( __FILE__, 'wpcomsp_wayback_link_fixer_activate' );
	register_uninstall_hook( __FILE__, 'wpcomsp_wayback_link_fixer_deactivate' );
}
