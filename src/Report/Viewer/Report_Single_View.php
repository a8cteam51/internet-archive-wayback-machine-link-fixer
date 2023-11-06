<?php

/**
 * Renders the report single view.
 *
 * @since      1.0.0
 * @version    1.0.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Viewer;

defined( 'ABSPATH' ) || exit;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report_Helper;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report_Repository;

/**
 * Single View
 */
class Report_Single_View {

	/**
	 * Access to the Report Repository.
	 *
	 * @since 1.0.0
	 *
	 * @var Report_Repository
	 */
	private Report_Repository $report_repository;

	/**
	 * Create instance of Report_List_View.
	 *
	 * @since 1.0.0
	 *
	 * @param Report_Repository $report_repository The Report Repository.
	 */
	public function __construct( Report_Repository $report_repository ) {
		$this->report_repository = $report_repository;
	}

	/**
	 * Renders the template.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function __invoke(): void {
		// Show an error if no report id is set in url.
		if ( ! isset( $_GET['report_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			// translators: %s is the page title.
			wp_die( esc_html( sprintf( __( 'No %s ID set.', 'wpcomsp_wayback_link_fixer' ), 'Report' ) ) );
		}

		// Get the sanitized ID.
		$report_id = \sanitize_text_field( $_GET['report_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$report    = $this->report_repository->find_by_report_id( $report_id );

		// If we have no report, show an error.
		if ( ! $report ) {
			// translators: %s is the page title.
			wp_die( esc_html( sprintf( __( 'No %s found.', 'wpcomsp_wayback_link_fixer' ), 'Report' ) ) );
		}

		$logs = $this->report_repository->get_logs( $report );

		// Render the single view.

		$list_page = Report_Helper::get_report_list_link();

		$report_author = \get_user_by( 'id', $report->get_user_id() ) ?: null; //phpcs:ignore Universal.Operators.DisallowShortTernary.Found

		$report_blog = get_bloginfo( $report->get_blog_id() );

		wpcomsp_wayback_link_fixer_render_template(
			'admin/reports/report-details.php',
			array(
				'report'     => $report,
				'logs'       => $logs,
				'back_url'   => $list_page,
				'author'     => $report_author,
				'site_title' => $report_blog,
			)
		);
	}
}
