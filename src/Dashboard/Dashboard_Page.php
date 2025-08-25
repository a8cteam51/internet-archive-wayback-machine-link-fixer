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
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report_Page;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link_Repository;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings_Page;

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

	public const PARENT_SLUG         = 'wpcomsp_wayback_link_fixer_dashboard';
	public const STATS_TRANSIENT_KEY = 'wblf_dashboard_stats';

	/**
	 * Access to the link repository.
	 *
	 * @var Link_Repository
	 */
	private $link_repository;

	/**
	 * Creates a new instance of the dashboard page.
	 */
	public function __construct() {
		$this->link_repository = new Link_Repository();
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

		add_action( 'admin_menu', array( $this, 'register_page' ) );
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
			'wpcomsp_wayback_link_fixer_dashboard',
			array( $this, 'render_page' ),
			'dashicons-admin-generic',
			100
		);
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
	 *     last_checks: list<LastCheck>
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

		// Limit the last checks to a sensible number.
		$last_check_limit = \absint( \apply_filters( 'wblf_dashboard_last_checks_limit', 10 ) );

		$stats = array(
			'total_links'           => count( $all_links ),
			'broken_links'          => count( $broken ),
			'links_with_archive'    => count( $has_archive_link ),
			'links_without_archive' => count( $all_links ) - count( $has_archive_link ),
			'not_checked'           => count( $not_checked ),
			'process_done'          => count( $process_done ),
			'process_new'           => count( $process_new ),
			'process_pending'       => count( $process_pending ),
			'last_checks'           => array_slice( $last_checks, 0, $last_check_limit ),
		);

		return $stats;
	}


	/**
	 * Render the dashboard page.
	 *
	 * @return void
	 */ public function render_page(): void {
		$link_stats = $this->get_statistics();

		$last_checks = array_map(
			function ( $check ) {
				return $this->get_link_stats( $check['id'] );
			},
			$link_stats['last_checks']
		);

		wpcomsp_wayback_link_fixer_render_template(
			'admin/dashboard/page.php',
			array(
				'wlf_link_stats'              => $link_stats,
				'wlf_last_checks'             => $last_checks,
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
			)
		);

		dump( $last_checks, $link_stats );
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
