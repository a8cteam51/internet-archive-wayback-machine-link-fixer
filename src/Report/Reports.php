<?php

/**
 * Report Service
 *
 * @since      1.0.0
 * @version    1.0.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Report;

defined( 'ABSPATH' ) || exit;

use DateTimeImmutable;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Link;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report;

/**
 * Report service.
 */
class Reports {

	public const PENDING_STATUS     = 'pending';
	public const IN_PROGRESS_STATUS = 'in-progress';
	public const COMPLETED_STATUS   = 'completed';

	/**
	 * Access to the report repository.
	 *
	 * @var Report_Repository
	 */
	private Report_Repository $report_repository;

	/**
	 * Create instance of Reports.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->report_repository = new Report_Repository();
	}

	/**
	 * Creates a new report for the current user and blog.
	 *
	 * @since 1.0.0
	 *
	 * @return Report
	 */
	public function create_report(): Report {
		$report = new Report(
			0,
			md5( rand() . time() ), //phpcs:ignore WordPress.WP.AlternativeFunctions.rand_rand
			get_current_user_id(),
			get_current_blog_id(),
			false,
			self::PENDING_STATUS,
			'',
			\current_time( 'mysql', true ),
		);

		return $this->report_repository->upsert( $report );
	}

	/**
	 * Mark a report as being in progress.
	 *
	 * @since 1.0.0
	 *
	 * @param Report $report The report to mark as in progress.
	 *
	 * @return Report
	 */
	public function mark_report_as_in_progress( Report $report ): Report {
		// If current report is already completed, throw an error.
		if ( self::COMPLETED_STATUS === $report->get_process() ) {
			throw new \Exception( 'Report is already completed.' );
		}

		$new = new Report(
			$report->get_id(),
			$report->get_report_id(),
			$report->get_user_id(),
			$report->get_blog_id(),
			$report->get_fixed(),
			self::IN_PROGRESS_STATUS,
			$report->get_description(),
			$report->get_created_at()->format( 'Y-m-d H:i:s' ),
		);

		return $this->report_repository->upsert( $new );
	}

	/**
	 * Mark a report as being completed.
	 *
	 * @since 1.0.0
	 *
	 * @param Report $report The report to mark as completed.
	 *
	 * @return Report
	 */
	public function mark_report_as_completed( Report $report ): Report {
		// If current report is already completed, throw an error.
		if ( self::COMPLETED_STATUS === $report->get_process() ) {
			throw new \Exception( 'Report is already completed.' );
		}

		$new = new Report(
			$report->get_id(),
			$report->get_report_id(),
			$report->get_user_id(),
			$report->get_blog_id(),
			$report->get_fixed(),
			self::COMPLETED_STATUS,
			$report->get_description(),
			$report->get_created_at()->format( 'Y-m-d H:i:s' ),
			\current_time( 'mysql', true ),
		);

		return $this->report_repository->upsert( $new );
	}

	/**
	 * Create a report for a single post.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $post_id The post ID.
	 *
	 * @return Report
	 */
	public function create_report_for_post( int $post_id ): Report {
		$report = new Report(
			0,
			md5( rand() . time() ), //phpcs:ignore WordPress.WP.AlternativeFunctions.rand_rand
			get_current_user_id(),
			get_current_blog_id(),
			false,
			self::PENDING_STATUS,
			__( 'Report for post: ', 'wpcomsp_wayback_link_fixer' ) . $post_id,
			\current_time( 'mysql', true )
		);

		return $this->report_repository->upsert( $report );
	}

	/**
	 * Log post for a report.
	 *
	 * @since 1.0.0
	 *
	 * @param Report           $report  The report to log the post for.
	 * @param integer          $post_id The post ID.
	 * @param array<int, Link> $links   The broken links.
	 *
	 * @return Log
	 */
	public function log_post_for_report( Report $report, int $post_id, array $links ): Log {

		$log = new Log(
			0,
			$report->get_id(),
			$post_id,
			\serialize( $links ) // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
		);

		return $this->report_repository->upsert_log( $log );
	}
}
