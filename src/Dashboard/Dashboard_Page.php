<?php

/**
 * The main dashboard page.
 *
 * @since 1.3.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Dashboard;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link_Repository;

defined( 'ABSPATH' ) || exit;

/**
 * Class used to render the main dashboard page.
 *
 * @phpstan-type LastCheck array{
 *     id: int<0, max>,
 *     last_check: array{
 *         date: non-empty-string,
 *         http_code: int<100, 599>
 *     }
 * }
 */
class Dashboard_Page {

	public const DASHBOARD_SLUG      = 'wayback-link-fixer-dashboard';
	public const STATS_TRANSIENT_KEY = 'wlf_dashboard_stats';

	/**
	 * Access to the link repository.
	 *
	 * @var Link_Repository
	 */
	private $link_repository;

	/**
	 * How many links to show in sections.
	 *
	 * @var int<1, max>
	 */
	private $links_per_section;

	/**
	 * Creates a new instance of the dashboard page.
	 */
	public function __construct() {
		$this->link_repository   = new Link_Repository();
		$this->links_per_section = apply_filters( 'wlf_dashboard_link_count', 10 );
	}

	/**
	 * Initialize the dashboard notifications.
	 *
	 * @return void
	 */
	public function initialize(): void {
		// If user can not access the reporting page, return.
		if ( ! current_user_can( Settings::get_reporting_page_capability() ) ) {
			return;
		}

		add_action( 'admin_menu', array( $this, 'register_page' ), 9 );
		add_action( 'admin_menu', array( $this, 'rename_first_submenu_item' ), 999 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_menu_icon_styles' ) );
	}


	/**
	 * Enqueue dashboard assets (styles and scripts).
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		$screen = get_current_screen();

		// Only load on our dashboard page and the main dashboard (for the widget)
		$is_dashboard_page = $screen && 'toplevel_page_' . self::DASHBOARD_SLUG === $screen->id;
		$is_main_dashboard = $screen && 'dashboard' === $screen->id;

		if ( $is_dashboard_page || $is_main_dashboard ) {
			// Enqueue styles
			wp_enqueue_style(
				self::DASHBOARD_SLUG,
				WPCOMSP_WAYBACK_LINK_FIXER_URL . 'assets/css/build/style-style.scss.css',
				array(),
				WPCOMSP_WAYBACK_LINK_FIXER_METADATA['Version']
			);
		}

		// Only enqueue scripts on our dashboard page
		if ( $is_dashboard_page ) {
			wp_enqueue_script(
				self::DASHBOARD_SLUG . '_dashboard',
				WPCOMSP_WAYBACK_LINK_FIXER_URL . 'assets/js/build/dashboard.js',
				array(),
				WPCOMSP_WAYBACK_LINK_FIXER_METADATA['Version'],
				true
			);
		}
	}

	/**
	 * Enqueue the custom menu icon styles.
	 *
	 * @return void
	 */
	public function enqueue_menu_icon_styles(): void {
		$menu_id = 'toplevel_page_' . self::DASHBOARD_SLUG;

		// Base64 encoded SVG - same approach as WooCommerce
		$svg_icon   = '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 32 32"><path fill="#a7aaad" d="M1.855 30.55h28.3V32h-28.3zm1.115-2.8h26.113v2H2.97zM2.804 4.882h25.974v2.8H2.804zm.166-.837H28.6l.78-.865L15.8 0 2.2 3.18zm3.58 12.46l-.112-4.1-.196-3.87c-.005-.1-.053-.135-.145-.156a5.41 5.41 0 0 0-2.294 0c-.092.02-.14.044-.145.156l-.196 3.87-.112 4.1.01 2.914.085 3.232.183 3.465.052.657a5.3 5.3 0 0 0 1.272.18c.422-.005.845-.07 1.272-.18l.052-.657.183-3.465.085-3.232.01-2.914zm7.072 0l-.112-4.1-.196-3.87c-.005-.1-.053-.135-.145-.156a5.41 5.41 0 0 0-2.294 0c-.092.02-.14.044-.145.156l-.196 3.87-.112 4.1.01 2.914.085 3.232.183 3.465.052.657a5.34 5.34 0 0 0 1.272.18c.422-.005.845-.07 1.272-.18l.052-.657.182-3.465.085-3.232.01-2.914zm8.202 0l-.112-4.1-.196-3.87c-.005-.1-.053-.135-.145-.156-.38-.083-.763-.122-1.147-.123a5.41 5.41 0 0 0-1.147.123c-.092.02-.14.044-.145.156l-.196 3.87-.112 4.1.01 2.914.085 3.232.183 3.465.052.657a5.34 5.34 0 0 0 1.272.18c.422-.005.845-.07 1.272-.18l.052-.657.183-3.465.085-3.232.01-2.914zm6.906 0l-.112-4.1-.196-3.87c-.005-.1-.053-.135-.145-.156-.38-.083-.763-.122-1.147-.123a5.41 5.41 0 0 0-1.147.123c-.092.02-.14.044-.145.156l-.196 3.87-.112 4.1.01 2.914.085 3.232.182 3.465.052.657a5.34 5.34 0 0 0 1.272.18 5.3 5.3 0 0 0 1.272-.18l.052-.657.183-3.465.085-3.232.01-2.914z"/></svg>';
		$base64_svg = base64_encode( $svg_icon );

		$custom_css = "
			/* Target only our specific menu item - WooCommerce style */
			li#{$menu_id} .wp-menu-image {
				background-image: url('data:image/svg+xml;base64,{$base64_svg}') !important;
				background-repeat: no-repeat !important;
				background-position: center !important;
				background-size: 20px 20px !important;
			}

			/* Remove dashicon */
			li#{$menu_id} .wp-menu-image:before {
				content: none !important;
			}
		";

		wp_add_inline_style( 'wp-admin', $custom_css );
	}

	/**
	 * Registers the dashboard page.
	 *
	 * @return void
	 */
	public function register_page(): void {
		add_menu_page(
			__( 'Wayback Link Fixer', 'internet-archive-wayback-machine-link-fixer' ),
			__( 'Wayback Link Fixer', 'internet-archive-wayback-machine-link-fixer' ),
			Settings::get_reporting_page_capability(),
			self::DASHBOARD_SLUG,
			array( $this, 'render_page' ),
			'', // Empty string for custom icon
			20
		);
	}

	/**
	 * Rename the first submenu item from "Wayback Link Fixer" to "Dashboard".
	 *
	 * @return void
	 */
	public function rename_first_submenu_item(): void {
		global $submenu;

		if ( isset( $submenu[ self::DASHBOARD_SLUG ] ) ) {
			// The first submenu item is always at index 0
			// Change the menu title (index 0 of the submenu item array)
			$submenu[ self::DASHBOARD_SLUG ][0][0] = __( 'Dashboard', 'internet-archive-wayback-machine-link-fixer' );
		}
	}

	/**
	 * Get the statistics for display on the dashboard.
	 *
	 * @return array{
	 *     total_links: int<0, max>,
	 *     broken_links: int<0, max>,
	 *     links_with_archive: int<0, max>,
	 *     links_without_archive: int<0, max>,
	 *     not_checked: int<0, max>,
	 *     process_done: int<0, max>,
	 *     process_new: int<0, max>,
	 *     process_pending: int<0, max>,
	 *     last_checks: list<LastCheck>
	 * }
	 */
	private function get_statistics(): array {
		// Attempt to get from the transient.
		$stats = get_transient( self::STATS_TRANSIENT_KEY );

		if ( false === $stats || ! is_array( $stats ) ) {
			$stats = $this->compile_statistics();

			// Store for 12 hours.
			set_transient( self::STATS_TRANSIENT_KEY, $stats, 12 * HOUR_IN_SECONDS );
		}
			$stats = $this->compile_statistics();

		// dd( $stats );

		return $stats;
	}


	/**
	 * Compile the statistics.
	 *
	 * @return array{
	 *     total_links: int<0, max>,
	 *     broken_links: int<0, max>,
	 *     links_with_archive: int<0, max>,
	 *     links_without_archive: int<0, max>,
	 *     not_checked: int<0, max>,
	 *     process_done: int<0, max>,
	 *     process_new: int<0, max>,
	 *     process_pending: int<0, max>,
	 * }
	 */
	private function compile_statistics(): array {
		$all_links = $this->link_repository->query_links( \PHP_INT_MAX, 1, array(), array(), array(), Link_Repository::ORDER_DATE_DESC, null, null, false );

		// Get all the links stats.
		$broken           = array();
		$has_archive_link = array();
		$not_checked      = array();
		$process_done     = array();
		$process_new      = array();
		$process_pending  = array();
		$last_checks      = array();

		// Loop through all links to gather stats.
		foreach ( $all_links as $link ) {
			if ( $link->is_broken() ) {
				$broken[] = $link->get_id();
			}
			if ( ! empty( $link->get_archived_href() ) ) {
				$has_archive_link[] = $link->get_id();
			}
			if ( null === $link->get_last_check() ) {
				$not_checked[] = $link->get_id();
			} else {
				$last          = $link->get_last_check();
				$last_checks[] = array(
					'id'         => $link->get_id(),
					'last_check' => $last,
				);
			}

			switch ( $link->get_archive_process() ) {
				case Link::PROCESS_NEW:
					$process_new[] = $link->get_id();
					break;
				case Link::PROCESS_PENDING:
					$process_pending[] = $link->get_id();
					break;
				default:
					$process_done[] = $link->get_id();
					break;
			}
		}

		// Sort the last checks by date desc.
		usort(
			$last_checks,
			function ( $a, $b ) {
				return strtotime( $b['last_check']['date'] ) <=> strtotime( $a['last_check']['date'] );
			}
		);

		$stats = array(
			'total_links'           => count( $all_links ),
			'broken_links'          => count( $broken ),
			'links_with_archive'    => count( $has_archive_link ),
			'links_without_archive' => count( $all_links ) - count( $has_archive_link ),
			'not_checked'           => count( $not_checked ),
			'process_done'          => count( $process_done ),
			'process_new'           => count( $process_new ),
			'process_pending'       => count( $process_pending ),
			'last_checks'           => array_slice( $last_checks, 0, $this->links_per_section ),
		);

		return $stats;
	}


	/**
	 * Render the dashboard page.
	 *
	 * @return void
	 */
	public function render_page(): void {
		$link_stats = $this->get_statistics();

		$last_checks = array_map(
			function ( $check ) {
				return $this->get_link_stats( $check['id'] );
			},
			$link_stats['last_checks']
		);

		$latest_links = array_map(
			function ( $link ) {
				return $this->get_link_stats( $link->get_id() );
			},
			$this->link_repository->query_links( $this->links_per_section, 1, array(), array(), array(), Link_Repository::ORDER_ID_DESC, null, null, false )
		);

		// Build the filtered links base url.
		$report_page_base  = Report_Page::get_page_url();
		$filtered_url_base = add_query_arg(
			array(
				'_wpnonce'         => \wp_create_nonce( 'bulk-reports' ),
				'_wp_http_referer' => \urldecode( $report_page_base ),
				'action'           => '-1',
				'action2'          => '-1',
				'paged'            => '1',
			),
			$report_page_base
		);
		// Has archive link.
		$has_archive_link = add_query_arg(
			array(
				'wlf_status'      => 'all',
				'wlf_has_archive' => '1',
			),
			$filtered_url_base
		);

		$has_no_archive_link = add_query_arg(
			array(
				'wlf_status'      => 'all',
				'wlf_has_archive' => '0',
			),
			$filtered_url_base
		);

		$broken_link = add_query_arg(
			array(
				'wlf_status' => '1',
			),
			$filtered_url_base
		);

		$valid_link = add_query_arg(
			array(
				'wlf_status' => '0',
			),
			$filtered_url_base
		);

		wpcomsp_wayback_link_fixer_render_template(
			'admin/dashboard/page.php',
			array(
				'wlf_link_stats'              => $link_stats,
				'wlf_last_checks'             => $last_checks,
				'wlf_latest_links'            => $latest_links,
				'wlf_account_details'         => Dashboard_Notifications::get_account_details(),
				'wlf_api_configured'          => Settings::is_archive_api_configured(),
				'wlf_is_online'               => wpcomsp_wayback_link_fixer_is_archive_api_online(),
				'wlf_link_to_settings'        => Settings_Page::get_page_url(),
				'wlf_link_table'              => Report_Page::get_page_url(),
				'wlf_auto_archiver_enabled'   => Settings::add_own_links(),
				'wlf_scan_existing_enabled'   => Settings::should_scan_existing_posts(),
				'wlf_link_processing_enabled' => Settings::is_link_processing_enabled(),
				'wlf_link_check_duration'     => Settings::get_link_check_duration(),
				'wlf_failed_check_count'      => Settings::get_failed_count(),
				'wlf_report_page_base'        => $report_page_base,
				'wlf_filtered_broken'         => esc_url( $broken_link ),
				'wlf_filtered_valid'          => esc_url( $valid_link ),
				'wlf_filtered_has_archive'    => esc_url( $has_archive_link ),
				'wlf_filtered_no_archive'     => esc_url( $has_no_archive_link ),
			)
		);
	}

	/**
	 * Get all stats for a given link id.
	 *
	 * @param int<0, max> $link_id The link ID to get stats for.
	 *
	 * @return array{link: Link, posts: list<\WP_Post>}|null
	 */
	public function get_link_stats( int $link_id ): ?array {
		$link = $this->link_repository->find_by_id( $link_id );

		if ( ! $link ) {
			return null;
		}

		$posts = $this->link_repository->get_post_ids_from_link_id( $link_id );

		return array(
			'link'  => $link,
			'posts' => array_map( 'get_post', array_filter( $posts ) ),
		);
	}
}
