<?php
/**
 * The wayback-link-fixer bootstrap file.
 *
 * @since       1.0.0
 * @version     1.0.0
 * @author      WordPress.com Special Projects
 * @license     GPL-3.0-or-later
 *
 * @noinspection    ALL
 *
 * @wordpress-plugin
 * Plugin Name:             wayback-link-fixer
 * Plugin URI:              https://wpspecialprojects.wordpress.com
 * Description:
 * Version:                 1.0.0
 * Requires at least:       6.2
 * Tested up to:            6.2
 * Requires PHP:            8.0
 * Author:                  WordPress.com Special Projects
 * Author URI:              https://wpspecialprojects.wordpress.com
 * License:                 GPL v3 or later
 * License URI:             https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:             wpcomsp-wayback-link-fixer
 * Domain Path:             /languages
 * WC requires at least:    7.4
 * WC tested up to:         7.4
 **/

defined( 'ABSPATH' ) || exit;

// Define plugin constants.
function_exists( 'get_plugin_data' ) || require_once ABSPATH . 'wp-admin/includes/plugin.php';
define( 'WPCOMSP_WAYBACK_LINK_FIXER_METADATA', get_plugin_data( __FILE__, false, false ) );

define( 'WPCOMSP_WAYBACK_LINK_FIXER_BASENAME', plugin_basename( __FILE__ ) );
define( 'WPCOMSP_WAYBACK_LINK_FIXER_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPCOMSP_WAYBACK_LINK_FIXER_URL', plugin_dir_url( __FILE__ ) );

$wpcomsp_wayback_link_fixer_requirements = validate_plugin_requirements( WPCOMSP_WAYBACK_LINK_FIXER_BASENAME );
define( 'WPCOMSP_WAYBACK_LINK_FIXER_REQUIREMENTS', $wpcomsp_wayback_link_fixer_requirements );

// Load plugin translations so they are available even for the error admin notices.
add_action(
	'init',
	static function () {
		load_plugin_textdomain(
			WPCOMSP_WAYBACK_LINK_FIXER_METADATA['TextDomain'],
			false,
			dirname( WPCOMSP_WAYBACK_LINK_FIXER_BASENAME ) . WPCOMSP_WAYBACK_LINK_FIXER_METADATA['DomainPath']
		);

		// Include the action scheduler integration.
		require_once WPCOMSP_WAYBACK_LINK_FIXER_PATH . 'lib/action-scheduler/action-scheduler.php';
	}
);

// Load the autoloader.
if ( ! is_file( WPCOMSP_WAYBACK_LINK_FIXER_PATH . '/vendor/autoload.php' ) ) {
	add_action(
		'admin_notices',
		static function () {
			$message      = __( 'It seems like <strong>wayback-link-fixer</strong> is corrupted. Please reinstall!', 'wpcomsp_wayback_link_fixer' );
			$html_message = wp_sprintf( '<div class="error notice wpcomsp-wayback-link-fixer-error">%s</div>', wpautop( $message ) );
			echo wp_kses_post( $html_message );
		}
	);
	return;
}
require_once WPCOMSP_WAYBACK_LINK_FIXER_PATH . '/vendor/autoload.php';



if ( $wpcomsp_wayback_link_fixer_requirements instanceof WP_Error ) {
	add_action(
		'admin_notices',
		static function () use ( $wpcomsp_wayback_link_fixer_requirements ): void {
			$html_message = wp_sprintf( '<div class="error notice wpcomsp-wayback-link-fixer-error">%s</div>', $wpcomsp_wayback_link_fixer_requirements->get_error_message() );
			echo wp_kses_post( $html_message );
		}
	);
} else {
	require_once WPCOMSP_WAYBACK_LINK_FIXER_PATH . 'functions.php';
	add_action( 'plugins_loaded', array( wpcomsp_wayback_link_fixer_get_plugin_instance(), 'maybe_initialize' ) );
}
