<?php

/**
 * Render the dashboard notifications.
 *
 * @since 1.3.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Dashboard;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report_Page;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link_Repository;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings_Page;

defined( 'ABSPATH' ) || exit;

/**
 * Class used to render the dashboard notifications.
 */
class Dashboard_Notifications {

	/**
	 * The page slug.
	 */
	const PAGE_SLUG = 'wayback_link_fixer_dashboard';

	/**
	 * Initialize the dashboard notifications.
	 *
	 * @return void
	 */
	public function initialize(): void {
		add_action( 'wp_dashboard_setup', array( $this, 'register_widgets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

	/**
	 * Enqueue the dashboard styles.
	 *
	 * @return void
	 */
	public function enqueue_styles(): void {
		$screen = get_current_screen();
		if ( $screen && 'dashboard' === $screen->id ) {
			wp_enqueue_style(
				self::PAGE_SLUG,
				WPCOMSP_WAYBACK_LINK_FIXER_URL . 'assets/css/build/style-style.scss.css',
				array(),
				WPCOMSP_WAYBACK_LINK_FIXER_METADATA['Version']
			);
		}
	}

	/**
	 * Register the dashboard widgets.
	 *
	 * @return void
	 */
	public function register_widgets(): void {
		wp_add_dashboard_widget(
			'wayback_link_fixer_dashboard_widget',
			__( 'Wayback Link Fixer', 'wpcomsp_wayback_link_fixer' ),
			array( $this, 'render_widget' ),
			null,
			null,
			'normal',
			'high'
		);
	}

	/**
	 * Render the dashboard widget.
	 *
	 * @return void
	 */
	public function render_widget(): void {
		wpcomsp_wayback_link_fixer_render_template(
			'admin/dashboard/widget.php',
			array(
				'wlf_details'                 => $this->get_account_details(),
				'wlf_api_configured'          => Settings::is_archive_api_configured(),
				'wlf_is_online'               => wpcomsp_wayback_link_fixer_is_archive_api_online(),
				'wlf_link_to_settings'        => Settings_Page::get_page_url(),
				'wlf_link_table'              => Report_Page::get_page_url(),
				'wlf_total_links'             => ( new Link_Repository() )->query_links( PHP_INT_MAX ),
				'wlf_auto_archiver_enabled'   => Settings::add_own_links(),
				'wlf_scan_existing_enabled'   => Settings::should_scan_existing_posts(),
				'wlf_link_processing_enabled' => Settings::is_link_processing_enabled(),
			)
		);
	}

	/**
	 * Get the sites account details from Archive.org.
	 *
	 * @return array{available:int, daily_captures:int, daily_captures_limit:int, processing:int}|null
	 */
	public static function get_account_details(): ?array {
		$cached = get_transient( 'wayback_link_fixer_account_details' );
		if ( false !== $cached ) {
			return $cached;
		}
		try {
			$details = \wpcomsp_wayback_link_fixer_get_system_client()->get_user_stats(
				Settings::get_archive_access_key(),
				Settings::get_archive_secret_key()
			);

			if ( is_array( $details ) ) {
				set_transient( 'wayback_link_fixer_account_details', $details, HOUR_IN_SECONDS );
				return array(
					'available'            => isset( $details['available'] ) ? (int) $details['available'] : 0,
					'daily_captures'       => isset( $details['daily_captures'] ) ? (int) $details['daily_captures'] : 0,
					'daily_captures_limit' => isset( $details['daily_captures_limit'] ) ? (int) $details['daily_captures_limit'] : 0,
					'processing'           => isset( $details['processing'] ) ? (int) $details['processing'] : 0,
				);
			}
		} catch ( \Exception $e ) {
			return null;
		}

		return null;
	}
}
