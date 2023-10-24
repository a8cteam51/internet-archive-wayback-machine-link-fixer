<?php

/**
 * Report Repository for interacting with the database.
 *
 * @since      1.0.0
 * @version    1.0.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Report;

defined( 'ABSPATH' ) || exit;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;

/**
 * Report Repository
 */
class Report_Repository {

	/**
	 * Get the report table name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	private function report_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . Settings::SCAN_REPORT_TABLE_NAME;
	}

	/**
	 * Get the log table name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	private function log_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . Settings::SCAN_LOG_TABLE_NAME;
	}


	/**
	 * Upsert a report.
	 *
	 * @since 1.0.0
	 *
	 * @param Report $report The report to upsert.
	 *
	 * @return Report
	 *
	 * @throws \Exception If there is an error upserting the report.
	 */
	public function upsert( Report $report ): Report {
		// If the report has an ID of 0, insert.
		if ( 0 === $report->get_id() ) {
			return $this->insert( $report );
		} else {
			return $this->update( $report );
		}
	}

	/**
	 * Insert a report to the database.
	 *
	 * @since 1.0.0
	 *
	 * @param Report $report The report to insert.
	 *
	 * @return Report
	 *
	 * @throws \Exception If there is an error inserting the report.
	 */
	public function insert( Report $report ): Report {
		global $wpdb;

		$wpdb->insert(
			$this->report_table_name(),
			array(
				'report_id'   => $report->get_report_id(),
				'user_id'     => $report->get_user_id(),
				'blog_id'     => $report->get_blog_id(),
				'fixed'       => $report->get_fixed() ? '1' : '0',
				'process'     => $report->get_process(),
				'description' => $report->get_description(),
				'create_date' => $report->get_created_at()->format( 'Y-m-d H:i:s' ),
			),
			array(
				'%s',
				'%d',
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
			)
		);

		// If we have an errors, throw an exception.
		if ( $wpdb->last_error ) {
			throw new \Exception( esc_html( $wpdb->last_error ) );
		}

		return new Report(
			$wpdb->insert_id,
			$report->get_report_id(),
			$report->get_user_id(),
			$report->get_blog_id(),
			$report->get_fixed(),
			$report->get_process(),
			$report->get_description(),
			$report->get_created_at()->format( 'Y-m-d H:i:s' ),
		);
	}

	/**
	 * Update a report in the database.
	 *
	 * @since 1.0.0
	 *
	 * @param Report $report The report to udpate.
	 *
	 * @return Report
	 *
	 * @throws \Exception If there is an error updating the report.
	 */
	public function update( Report $report ): Report {
		global $wpdb;

		$wpdb->update(
			$this->report_table_name(),
			array(
				'report_id'      => $report->get_report_id(),
				'user_id'        => $report->get_user_id(),
				'blog_id'        => $report->get_blog_id(),
				'fixed'          => $report->get_fixed() ? '1' : '0',
				'process'        => $report->get_process(),
				'description'    => $report->get_description(),
				'create_date'    => $report->get_created_at()->format( 'Y-m-d H:i:s' ),
				'completed_date' => null !== $report->get_completed_at()
					? $report->get_completed_at()->format( 'Y-m-d H:i:s' )
					: '',
			),
			array(
				'id' => $report->get_id(),
			),
			array(
				'%s',
				'%d',
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
			),
			array(
				'%d',
			)
		);

		// If we have an errors, throw an exception.
		if ( $wpdb->last_error ) {
			throw new \Exception( esc_html( $wpdb->last_error ) );
		}

		return $report;
	}

	/**
	 * Get a report based on its id.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $id The report id.
	 *
	 * @return Report|null
	 */
	public function find( int $id ): ?Report {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->report_table_name()} WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, Table name cant be prepared.
				$id
			)
		);

		if ( ! $row ) {
			return null;
		}

		return $this->map_row_to_report( $row );
	}

	/**
	 * Create log for report.
	 *
	 * @since 1.0.0
	 *
	 * @param Report $report The report to log.
	 *
	 * @return Log
	 */
	public function create_log_for_report( Report $report ): Log {
		return new Log(
			0,
			$report->get_id(),
			0,
			'[]',
			'[]',
		);
	}

	/**
	 * Maps a DB row to a report.
	 *
	 * @since 1.0.0
	 *
	 * @template T of \stdClass{
	 *   id:int,
	 *   report_id:string,
	 *   user_id:integer,
	 *   blog_id:integer
	 *   fixed:integer
	 *   process:string,
	 *   description:string,
	 *   create_date:string
	 *   completed_date:string
	 * }
	 *
	 * @param T $row The DB row.
	 *
	 * @return Report
	 */
	private function map_row_to_report( \stdClass $row ): Report {  //phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint, Using <T> Template

		// Treat 0000-00-00 00:00:00 as null.
		$completed = null !== $row->completed_date && '0000-00-00 00:00:00' !== $row->completed_date
			? $row->completed_date
			: null;

		return new Report(
			$row->id,
			$row->report_id,
			$row->user_id,
			$row->blog_id,
			(bool) $row->fixed,
			$row->process,
			$row->description,
			$row->create_date,
			$completed,
		);
	}

	/**
	 * Get all reports started by a user.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $user_id The user id.
	 *
	 * @return array<int, Report>
	 */
	public function by_user( int $user_id ): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->report_table_name()} WHERE user_id = %d", //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, cant prepare table names.
				$user_id
			)
		);

		if ( ! $rows ) {
			return array();
		}

		return array_map( array( $this, 'map_row_to_report' ), $rows );
	}

	/**
	 * Find all reports by status.
	 *
	 * @since 1.0.0
	 *
	 * @param string $status The status to find.
	 *
	 * @return array<int, Report>
	 */
	public function by_status( string $status ): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->report_table_name()} WHERE process = %s", //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, cant prepare table names.
				$status
			)
		);

		if ( ! $rows ) {
			return array();
		}

		return array_map( array( $this, 'map_row_to_report' ), $rows );
	}

	/**
	 * Upsert a log.
	 *
	 * @since 1.0.0
	 *
	 * @param Log $log The log to upsert.
	 *
	 * @return Log
	 */
	public function upsert_log( Log $log ): Log {
		return 0 === $log->get_id()
			? $this->create_log( $log )
			: $this->update_log( $log );
	}

	/**
	 * Creates a log in the DB.
	 *
	 * @since 1.0.0
	 *
	 * @param Log $log The log to create.
	 *
	 * @return Log
	 */
	public function create_log( Log $log ): Log {
		global $wpdb;

		$wpdb->insert(
			$this->log_table_name(),
			array(
				'report_id'    => $log->get_report_id(),
				'post_id'      => $log->get_post_id(),
				'broken_links' => $log->get_broken_links_json(),
				'replacements' => $log->get_replacements_json(),
			),
			array(
				'%s',
				'%d',
				'%s',
				'%s',
			)
		);

		return new Log(
			$wpdb->insert_id,
			$log->get_report_id(),
			$log->get_post_id(),
			$log->get_broken_links_json(),
			$log->get_replacements_json(),
		);
	}

	/**
	 * Update a log in the DB.
	 *
	 * @since 1.0.0
	 *
	 * @param Log $log The log to update.
	 *
	 * @return Log
	 */
	public function update_log( Log $log ): Log {
		global $wpdb;

		$wpdb->update(
			$this->log_table_name(),
			array(
				'report_id'    => $log->get_report_id(),
				'post_id'      => $log->get_post_id(),
				'broken_links' => $log->get_broken_links_json(),
				'replacements' => $log->get_replacements_json(),
			),
			array(
				'id' => $log->get_id(),
			),
			array(
				'%s',
				'%d',
				'%s',
				'%s',
			),
			array(
				'%d',
			)
		);

		return $log;
	}
}
