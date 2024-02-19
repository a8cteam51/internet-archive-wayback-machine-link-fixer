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

use WPCOMSpecialProjects\Wayback_Link_Fixer\Runner\Runner;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Updater\Updater;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Migration\Migrations;
use WPCOMSpecialProjects\Wayback_Link_Fixer_Migration\Migration_1;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Analyzer\Content_Analyzer;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Analyzer\Runner\Scheduled_Runner;

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
	// Add all migrations.
	\WPCOMSpecialProjects\Wayback_Link_Fixer\Migration\Migrations::$migrations = array( //phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
		\WPCOMSpecialProjects\Wayback_Link_Fixer_Migration\Migration_1::class,
	);


	require_once WPCOMSP_WAYBACK_LINK_FIXER_PATH . 'functions.php';
	add_action( 'plugins_loaded', array( wpcomsp_wayback_link_fixer_get_plugin_instance(), 'maybe_initialize' ) );

	register_activation_hook( __FILE__, 'wpcomsp_wayback_link_fixer_activate' );
	register_uninstall_hook( __FILE__, 'wpcomsp_wayback_link_fixer_deactivate' );
}


add_action('init', function(){
	// $r = 'C:51:"WPCOMSpecialProjects\\Wayback_Link_Fixer\\Event\\Event":1351:{a:6:{s:8:"post_ids";a:11:{i:0;i:65;i:1;i:67;i:2;i:66;i:3;i:63;i:4;i:64;i:5;i:15;i:6;i:9;i:7;i:6;i:8;i:2;i:9;i:1;i:10;i:3;}s:10:"http_codes";a:8:{i:0;s:3:"200";i:1;s:3:"300";i:2;s:3:"301";i:3;s:3:"303";i:4;s:3:"404";i:5;s:3:"410";i:6;s:3:"500";i:7;s:3:"502";}s:12:"ignore_cache";b:1;s:6:"report";s:998:"O:53:"WPCOMSpecialProjects\\Wayback_Link_Fixer\\Report\\Report":8:{s:57:"' . "\0" . 'WPCOMSpecialProjects\\Wayback_Link_Fixer\\Report\\Report' . "\0" . 'id";i:80;s:64:"' . "\0" . 'WPCOMSpecialProjects\\Wayback_Link_Fixer\\Report\\Report' . "\0" . 'report_id";s:32:"51074f87acc028b59ad4cbf44b118a3b";s:62:"' . "\0" . 'WPCOMSpecialProjects\\Wayback_Link_Fixer\\Report\\Report' . "\0" . 'user_id";i:1;s:62:"' . "\0" . 'WPCOMSpecialProjects\\Wayback_Link_Fixer\\Report\\Report' . "\0" . 'blog_id";i:1;s:62:"' . "\0" . 'WPCOMSpecialProjects\\Wayback_Link_Fixer\\Report\\Report' . "\0" . 'process";s:7:"pending";s:66:"' . "\0" . 'WPCOMSpecialProjects\\Wayback_Link_Fixer\\Report\\Report' . "\0" . 'description";s:162:"Event created for all posts from the post,page post types, with 200,300,301,303,404,410,500,502 http codes, ignoring posts [], ignoring the cache and fixing links";s:65:"' . "\0" . 'WPCOMSpecialProjects\\Wayback_Link_Fixer\\Report\\Report' . "\0" . 'created_at";O:17:"DateTimeImmutable":3:{s:4:"date";s:26:"2024-02-19 14:46:50.000000";s:13:"timezone_type";i:3;s:8:"timezone";s:3:"UTC";}s:67:"' . "\0" . 'WPCOMSpecialProjects\\Wayback_Link_Fixer\\Report\\Report' . "\0" . 'completed_at";N;}";s:9:"processed";a:0:{}s:14:"auto_fix_links";b:1;}}';


	// $runner = new Scheduled_Runner();
	// $runner->__invoke($r);
});
