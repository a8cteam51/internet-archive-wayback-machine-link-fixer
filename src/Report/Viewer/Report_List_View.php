<?php

/**
 * Renders the report list view.
 *
 * @since      1.0.0
 * @version    1.0.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Viewer;

defined( 'ABSPATH' ) || exit;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report_Repository;

/**
 * List View
 */
class Report_List_View {

	## All URL Params.
	public const PARAM_REPORTS_PER_PAGE = 'per_page';
	public const PARAM_CURRENT_PAGE     = 'paged';
	public const PARAM_BLOG_ID          = 'blog_id';
	public const PARAM_USER_ID          = 'user_id';
	public const PARAM_STATUS           = 'status';
	public const PARAM_DATE_FROM        = 'date_from';
	public const PARAM_DATE_TO          = 'date_to';


	/**
	 * Holds how many reports per page to show.
	 *
	 * @since 1.0.0
	 *
	 * @var integer
	 */
	private int $reports_per_page = 10;

	/**
	 * Holds the current page number.
	 *
	 * @since 1.0.0
	 *
	 * @var integer
	 */
	private int $current_page = 1;

	/**
	 * Holds the total number of reports.
	 *
	 * @since 1.0.0
	 *
	 * @var integer
	 */
	private int $total_reports = 0;

	/**
	 * Holds the total number of pages.
	 *
	 * @since 1.0.0
	 *
	 * @var integer
	 */
	private int $total_pages = 0;

	/**
	 * Holds any additional filters.
	 *
	 * @since 1.0.0
	 *
	 * @var array{blog_id:integer|null, user_id:integer|null, status:string[], date_from:string|null, date_to:string|null}
	 */
	private array $filters = array(
		'blog_id'   => null,
		'user_id'   => null,
		'status'    => array(),
		'date_from' => null,
		'date_to'   => null,
	);

	/**
	 * Access to the Report Repository.
	 *
	 * @since 1.0.0
	 *
	 * @var Report_Repository
	 */
	private Report_Repository $report_repository;

	/**
	 * All reports to list currently.
	 *
	 * @since 1.0.0
	 *
	 * @var array<int, Report>
	 */
	private array $reports = array();

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
	 * Set the filter and view args.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function set_filters(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		// Set per page abd current page.
		$this->reports_per_page = isset( $_GET[ self::PARAM_REPORTS_PER_PAGE ] ) ? absint( $_GET[ self::PARAM_REPORTS_PER_PAGE ] ) : 10;
		$this->current_page     = isset( $_GET[ self::PARAM_CURRENT_PAGE ] ) ? absint( $_GET[ self::PARAM_CURRENT_PAGE ] ) : 1;
		// Set the filters.
		$this->filters['blog_id']   = isset( $_GET[ self::PARAM_BLOG_ID ] ) ? intval( $_GET[ self::PARAM_BLOG_ID ] ) : null;
		$this->filters['status']    = isset( $_GET[ self::PARAM_STATUS ] ) ? (array) $_GET[ self::PARAM_STATUS ] : array();
		$this->filters['date_from'] = isset( $_GET[ self::PARAM_DATE_FROM ] ) ? sanitize_text_field( $_GET[ self::PARAM_DATE_FROM ] ) : null;
		$this->filters['date_to']   = isset( $_GET[ self::PARAM_DATE_TO ] ) ? sanitize_text_field( $_GET[ self::PARAM_DATE_TO ] ) : null;

		// Set the user id. DO not treat empty as 0
		$this->filters['user_id'] = ( function () {
			// If the user id is set, return null.
			if ( ! isset( $_GET[ self::PARAM_USER_ID ] ) || '' === $_GET[ self::PARAM_USER_ID ] ) {
				return null;
			}
			return absint( $_GET[ self::PARAM_USER_ID ] );
		} )();
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Set all the reports.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function get_reports(): void {

		// Get the offset based on the current page.
		$offset = 1 === $this->current_page
			? 0
			: ( $this->current_page - 1 ) * $this->reports_per_page;

		$this->reports = $this->report_repository->query_reports(
			$this->reports_per_page,
			$offset,
			$this->filters['user_id'],
			$this->filters['blog_id'],
			$this->filters['status'],
			$this->filters['date_from'],
			$this->filters['date_to']
		);
	}

	/**
	 * Set the total report count.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function set_total_reports(): void {
		$this->total_reports = $this->report_repository->get_total_count(
			$this->filters['user_id'],
			$this->filters['blog_id'],
			$this->filters['status'],
			$this->filters['date_from'],
			$this->filters['date_to']
		);

		// Work out the total number of pages.
		if ( 0 === $this->total_reports || 0 === $this->reports_per_page ) {
			$this->total_pages = 0;
		} else {
			$this->total_pages = (int) ceil( $this->total_reports / $this->reports_per_page );
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
		// Set the filters and get all matching reports.
		$this->set_filters();
		$this->get_reports();
		$this->set_total_reports();

		wpcomsp_wayback_link_fixer_render_template(
			'admin/reports/report-list.php',
			array(
				'current_page'     => $this->current_page,
				'total_pages'      => $this->total_pages,
				'filters'          => $this->filters,
				'reports'          => $this->reports,
				'total_reports'    => $this->total_reports,
				'reports_per_page' => $this->reports_per_page,
			)
		);
	}
}
