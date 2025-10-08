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
	public const STATS_TRANSIENT_KEY = 'iawmlf_dashboard_stats';

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
		$this->links_per_section = absint( apply_filters( 'iawmlf_dashboard_link_count', 10 ) );
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
				IAWMLF_URL . 'assets/css/build/style-style.scss.css',
				array(),
				IAWMLF_VERSION
			);
		}

		// Only enqueue scripts on our dashboard page
		if ( $is_dashboard_page ) {
			wp_enqueue_script(
				self::DASHBOARD_SLUG . '_dashboard',
				IAWMLF_URL . 'assets/js/build/dashboard.js',
				array(),
				IAWMLF_VERSION,
				true
			);
		}
	}

	/**
	 * Returns the base64 encoded SVG icon for the menu.
	 *
	 * @return string
	 */
	private function get_ia_icon_base64(): string {
		$icon = 'PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjYwIDYwIDE0MCAxNDAiIGZpbGw9ImN1cnJlbnRDb2xvciI+PHBhdGggZD0iTTE3MS45MjggMTA5LjcwNEMxNzkuMDA3IDEwMi42MjUgMTc5LjAwNyA5MS4xNDkxIDE3MS45MjggODQuMDcwOUMxNjQuODUgNzYuOTkyNiAxNTMuMzc0IDc2Ljk5MjYgMTQ2LjI5NiA4NC4wNzA5TDg0LjA3MDQgMTQ2LjI5NkM3Ni45OTIyIDE1My4zNzUgNzYuOTkyMiAxNjQuODUxIDg0LjA3MDQgMTcxLjkyOUM5MS4xNDg3IDE3OS4wMDcgMTAyLjYyNSAxNzkuMDA3IDEwOS43MDMgMTcxLjkyOUwxNzEuOTI4IDEwOS43MDRaTTExNi41OTcgMTc4LjgyM0MxMDUuNzExIDE4OS43MDkgODguMDYyIDE4OS43MDkgNzcuMTc2MSAxNzguODIzQzY2LjI5MDMgMTY3LjkzNyA2Ni4yOTAzIDE1MC4yODggNzcuMTc2MSAxMzkuNDAyTDEzOS40MDIgNzcuMTc2NkMxNTAuMjg3IDY2LjI5MDcgMTY3LjkzNyA2Ni4yOTA3IDE3OC44MjMgNzcuMTc2NkMxODkuNzA5IDg4LjA2MjUgMTg5LjcwOSAxMDUuNzEyIDE3OC44MjMgMTE2LjU5OEwxMTYuNTk3IDE3OC44MjNaIi8+PHBhdGggZD0iTTE3OC44MjMgMTM5LjQwMkMxODkuNzA5IDE1MC4yODggMTg5LjcwOSAxNjcuOTM3IDE3OC44MjMgMTc4LjgyM0MxNjcuOTM3IDE4OS43MDkgMTUwLjI4OCAxODkuNzA5IDEzOS40MDIgMTc4LjgyM0wxMzUuMTU5IDE3NC41OEwxNDIuMDUzIDE2Ny42ODZMMTQ2LjI5NiAxNzEuOTI5QzE1My4zNzUgMTc5LjAwNyAxNjQuODUgMTc5LjAwNyAxNzEuOTI4IDE3MS45MjlDMTc5LjAwNiAxNjQuODUgMTc5LjAwNiAxNTMuMzc1IDE3MS45MjggMTQ2LjI5N0wxNjcuNjg1IDE0Mi4wNTRMMTc0LjU4IDEzNS4xNTlMMTc4LjgyMyAxMzkuNDAyWk03Ny4xNzYyIDc3LjE3NjdDODguMDYyIDY2LjI5MSAxMDUuNzExIDY2LjI5MTEgMTE2LjU5NyA3Ny4xNzY3TDEyMC44NCA4MS40MTk5TDExMy45NDYgODguMzEzNUwxMDkuNzA0IDg0LjA3MTNDMTAyLjYyNSA3Ni45OTMgOTEuMTQ5IDc2Ljk5MyA4NC4wNzA4IDg0LjA3MTNDNzYuOTkyNSA5MS4xNDk1IDc2Ljk5MjUgMTAyLjYyNiA4NC4wNzA4IDEwOS43MDRMODguMzEzIDExMy45NDZMODEuNDE5NCAxMjAuODQxTDc3LjE3NjIgMTE2LjU5OEM2Ni4yOTA2IDEwNS43MTIgNjYuMjkwNSA4OC4wNjI1IDc3LjE3NjIgNzcuMTc2N1oiLz48cGF0aCBkPSJNMTY3LjQyMSAxMjhMMTI3Ljk5OSAxNjcuNDIxTDg4LjU3ODIgMTI4TDEyNy45OTkgODguNTc4N0wxNjcuNDIxIDEyOFpNMTAyLjM2NyAxMjhMMTI3Ljk5OSAxNTMuNjMzTDE1My42MzIgMTI4TDEyNy45OTkgMTAyLjM2N0wxMDIuMzY3IDEyOFoiLz48cGF0aCBkPSJNMTMwLjEyMSAxMTguODA4QzEyOC41NTkgMTIwLjM3IDEyNi4wMjYgMTIwLjM3IDEyNC40NjQgMTE4LjgwOEMxMjIuOTAyIDExNy4yNDUgMTIyLjkwMiAxMTQuNzEzIDEyNC40NjQgMTEzLjE1MUMxMjYuMDI2IDExMS41ODkgMTI4LjU1OSAxMTEuNTg5IDEzMC4xMjEgMTEzLjE1MUMxMzEuNjgzIDExNC43MTMgMTMxLjY4MyAxMTcuMjQ1IDEzMC4xMjEgMTE4LjgwOFoiLz48cGF0aCBkPSJNMTE5LjUxNCAxMjkuNDE0QzExNy45NTIgMTMwLjk3NiAxMTUuNDE5IDEzMC45NzYgMTEzLjg1NyAxMjkuNDE0QzExMi4yOTUgMTI3Ljg1MiAxMTIuMjk1IDEyNS4zMTkgMTEzLjg1NyAxMjMuNzU3QzExNS40MTkgMTIyLjE5NSAxMTcuOTUyIDEyMi4xOTUgMTE5LjUxNCAxMjMuNzU3QzEyMS4wNzYgMTI1LjMxOSAxMjEuMDc2IDEyNy44NTIgMTE5LjUxNCAxMjkuNDE0WiIvPjxwYXRoIGQ9Ik0xNDIuODQ5IDEzMS41MzVDMTQxLjI4NyAxMzMuMDk4IDEzOC43NTQgMTMzLjA5OCAxMzcuMTkyIDEzMS41MzVDMTM1LjYzIDEyOS45NzMgMTM1LjYzIDEyNy40NDEgMTM3LjE5MiAxMjUuODc5QzEzOC43NTQgMTI0LjMxNiAxNDEuMjg3IDEyNC4zMTYgMTQyLjg0OSAxMjUuODc5QzE0NC40MTEgMTI3LjQ0MSAxNDQuNDExIDEyOS45NzMgMTQyLjg0OSAxMzEuNTM1WiIvPjxwYXRoIGQ9Ik0xMzIuMjQyIDE0Mi4xNDJDMTMwLjY4IDE0My43MDQgMTI4LjE0NyAxNDMuNzA0IDEyNi41ODUgMTQyLjE0MkMxMjUuMDIzIDE0MC41OCAxMjUuMDIzIDEzOC4wNDcgMTI2LjU4NSAxMzYuNDg1QzEyOC4xNDcgMTM0LjkyMyAxMzAuNjggMTM0LjkyMyAxMzIuMjQyIDEzNi40ODVDMTMzLjgwNCAxMzguMDQ3IDEzMy44MDQgMTQwLjU4IDEzMi4yNDIgMTQyLjE0MloiLz48L3N2Zz4K';
		return \apply_filters( 'iawmlf_menu_icon_base64', $icon );
	}


	/**
	 * Registers the dashboard page.
	 *
	 * @return void
	 */
	public function register_page(): void {
		add_menu_page(
			__( 'Wayback Link Fixer', 'internet-archive-wayback-machine-link-fixer' ),
			__( 'Link Fixer', 'internet-archive-wayback-machine-link-fixer' ),
			Settings::get_reporting_page_capability(),
			self::DASHBOARD_SLUG,
			array( $this, 'render_page' ),
			'data:image/svg+xml;base64,' . $this->get_ia_icon_base64(),
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
			$submenu[ self::DASHBOARD_SLUG ][0][0] = __( 'Dashboard', 'internet-archive-wayback-machine-link-fixer' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited, this is how WP works
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

			// Store for 2 hours.
			set_transient( self::STATS_TRANSIENT_KEY, $stats, 2 * HOUR_IN_SECONDS );
		}

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
		$last_checks = array_values( array_filter( $last_checks ) );

		$latest_links = array_map(
			function ( $link ) {
				return $this->get_link_stats( $link->get_id() );
			},
			$this->link_repository->query_links( $this->links_per_section, 1, array(), array(), array(), Link_Repository::ORDER_ID_DESC, null, null, false )
		);
		$latest_links = array_values( array_filter( $latest_links ) );

		// Build the filtered links base url.
		$report_page_base  = Report_Page::get_page_url();
		$filtered_url_base = add_query_arg(
			array(
				'_wpnonce'         => \wp_create_nonce( 'bulk-reports' ),
				'_wp_http_referer' => $report_page_base,
				'action'           => '-1',
				'action2'          => '-1',
				'paged'            => '1',
			),
			$report_page_base
		);
		// Has archive link.
		$has_archive_link = add_query_arg(
			array(
				'iawmlf_status'      => 'all',
				'iawmlf_has_archive' => '1',
			),
			$filtered_url_base
		);

		$has_no_archive_link = add_query_arg(
			array(
				'iawmlf_status'      => 'all',
				'iawmlf_has_archive' => '0',
			),
			$filtered_url_base
		);

		$broken_link = add_query_arg(
			array(
				'iawmlf_status' => '1',
			),
			$filtered_url_base
		);

		$valid_link = add_query_arg(
			array(
				'iawmlf_status' => '0',
			),
			$filtered_url_base
		);

		iawmlf_render_template(
			'admin/dashboard/page.php',
			array(
				'iawmlf_link_stats'              => $link_stats,
				'iawmlf_last_checks'             => $last_checks,
				'iawmlf_latest_links'            => $latest_links,
				'iawmlf_account_details'         => Dashboard_Notifications::get_account_details(),
				'iawmlf_api_configured'          => Settings::is_archive_api_configured(),
				'iawmlf_is_online'               => iawmlf_is_archive_api_online(),
				'iawmlf_link_to_settings'        => Settings_Page::get_page_url(),
				'iawmlf_link_table'              => Report_Page::get_page_url(),
				'iawmlf_auto_archiver_enabled'   => Settings::add_own_links(),
				'iawmlf_scan_existing_enabled'   => Settings::should_scan_existing_posts(),
				'iawmlf_link_processing_enabled' => Settings::is_link_processing_enabled(),
				'iawmlf_link_check_duration'     => Settings::get_link_check_duration(),
				'iawmlf_failed_check_count'      => Settings::get_failed_count(),
				'iawmlf_report_page_base'        => $report_page_base,
				'iawmlf_filtered_broken'         => esc_url( $broken_link ),
				'iawmlf_filtered_valid'          => esc_url( $valid_link ),
				'iawmlf_filtered_has_archive'    => esc_url( $has_archive_link ),
				'iawmlf_filtered_no_archive'     => esc_url( $has_no_archive_link ),
			)
		);
	}

	/**
	 * Get all stats for a given link id.
	 *
	 * @param positive-int $link_id The link ID to get stats for.
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
