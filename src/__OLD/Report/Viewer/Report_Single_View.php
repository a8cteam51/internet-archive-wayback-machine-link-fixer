<?php

/**
 * Renders the report single view.
 *
 * @since      1.0.0
 * @version    1.0.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Viewer;

defined( 'ABSPATH' ) || exit;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report_Helper;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report_Repository;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\List_Table\Report_Table;

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
	 * The report to render.
	 *
	 * @since 1.1.0
	 *
	 * @var Report
	 */
	private Report $report;

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
	 * Validates the request.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 *
	 * @throws \Exception If the request is invalid.
	 */
	private function validate_request(): void {
		// If report id is not set, throw an exception.
		if ( ! isset( $_GET['report_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			throw new \Exception( esc_html__( 'No report ID set.', 'wpcomsp_wayback_link_fixer' ) );
		}

		// Get the sanitized ID and check we have a report.
		$report_id = \sanitize_text_field( $_GET['report_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$this->report = $this->report_repository->find_by_report_id( $report_id );

		if ( ! $this->report ) {
			throw new \Exception( esc_html__( 'No report found.', 'wpcomsp_wayback_link_fixer' ) );
		}
	}



	/**
	 * Renders the template.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function __invoke(): void {
		try {
			$this->validate_request();
		} catch ( \Throwable $th ) {
			wp_die( esc_html( $th->getMessage() ) );
		}

		$logs = $this->report_repository->get_logs( $this->report );

		// Render the single view.
		$table = new Report_Table( $this->report, $logs );

		// Run the bulk actions.
		$table->process_bulk_action();

		// Render any notices.
		$table->render_notices();

		echo '<div class="wrap">';
		printf(
			'<h1 class="wp-heading-inline">%s <a id="wlf-report-csv-download" class="page-title-action wlf-download-report-csv" data-report="%s">%s</a></h1>',
			esc_html__( 'Reports', 'wpcomsp_wayback_link_fixer' ),
			esc_attr( $this->report->get_report_id() ),
			esc_html__( 'Download CSV', 'wpcomsp_wayback_link_fixer' )
		);

		echo '<hr class="wp-header-end">';
		echo '<div id="wlf-report-notifications"></div>';
		// Render the report details.

		wpcomsp_wayback_link_fixer_render_template(
			'admin/reports/report-details.php',
			array(
				'report'     => $this->report,
				'back_url'   => Report_Helper::get_report_list_link(),
				'logs'       => $logs,
				'author'     => null !== $this->report->get_user_id() ? \get_user_by( 'id', $this->report->get_user_id() ) : null,
				'site_title' => get_bloginfo( $this->report->get_blog_id() ),
			)
		);
		// Render the table.
		$table->prepare_items();
		echo '<form method="get">';
		$table->display();
		echo '</form>';

		echo '<div id="wlf-modal" class="wlf-modal" style="display:none">';
		echo '<div class="wlf-modal__inner">';
		echo '<div id="wlf-modal__inner-header">';
		echo '<p id="wlf-modal__inner-header-title">Modal</p>';
		echo '<div id="wlf-modal__inner-header-close"><span class="dashicons dashicons-dismiss"></span></div>';
		echo '</div>';
		echo '<div id="wlf-modal__inner-content">';
		echo '<p></p>';
		echo '</div>';
		echo '</div>';

		echo '</div>';
	}
}
