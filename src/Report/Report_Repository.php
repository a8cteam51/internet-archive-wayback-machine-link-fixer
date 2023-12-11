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
				'process'     => $report->get_process(),
				'description' => $report->get_description(),
				'create_date' => $report->get_created_at()->format( 'Y-m-d H:i:s' ),
			),
			array(
				'%s',
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
	 * Find a Report based on its Report ID.
	 *
	 * @since 1.0.0
	 *
	 * @param string $report_id The report id.
	 *
	 * @return Report|null
	 */
	public function find_by_report_id( string $report_id ): ?Report {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->report_table_name()} WHERE report_id = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, Table name cant be prepared.
				$report_id
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
	 * Get the logs for a report.
	 *
	 * @since 1.0.0
	 *
	 * @param Report $report The report to get the logs for.
	 *
	 * @return array<int, Log>
	 */
	public function get_logs( Report $report ): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->log_table_name()} WHERE report_id = %d", //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, cant prepare table names.
				$report->get_id()
			)
		);

		if ( ! $rows ) {
			return array();
		}

		return array_map(
			function ( \stdClass $row ): Log {
				return new Log(
					$row->id,
					$row->report_id,
					$row->post_id,
					$row->links
				);
			},
			$rows
		);
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
				'report_id' => $log->get_report_id(),
				'post_id'   => $log->get_post_id(),
				'links'     => $log->get_serialized_links(),
			),
			array(
				'%s',
				'%d',
				'%s',
			)
		);

		return new Log(
			$wpdb->insert_id,
			$log->get_report_id(),
			$log->get_post_id(),
			$log->get_serialized_links()
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
				'report_id' => $log->get_report_id(),
				'post_id'   => $log->get_post_id(),
				'links'     => $log->get_serialized_links(),
			),
			array( 'id' => $log->get_id() ),
			array(
				'%s',
				'%d',
				'%s',
			),
			array( '%d' )
		);

		return $log;
	}

	/**
	 * Query reports.
	 *
	 * @since 1.0.0
	 *
	 * @param integer      $limit     The number of reports per call.
	 * @param integer      $offset    The offset to start at.
	 * @param integer|null $user_id   The user id to filter by.
	 * @param integer|null $blog_id   The blog id to filter by.
	 * @param string[]     $statuses  The statuses to filter by.
	 * @param string|null  $date_from The date to filter from.
	 * @param string|null  $date_to   The date to filter to.
	 *
	 * @return array<int, array{report: Report, logs: int}> Returns the Report and count of number of logs.
	 */
	public function query_reports(
		int $limit = 10,
		int $offset = 0,
		?int $user_id = null,
		?int $blog_id = null,
		array $statuses = array(),
		?string $date_from = null,
		?string $date_to = null
	): array {

		global $wpdb;

		// Build the query.
		$query = "SELECT Reports.*, Reports.report_id as report_ids, Logs.* FROM {$this->report_table_name()} as Reports";

		// Join the count of logs.
		$query .= " LEFT JOIN (SELECT report_id, COUNT(*) as logs FROM {$this->log_table_name()} GROUP BY report_id) as Logs ON Reports.id = Logs.report_id";

		// If we have a where clause, add it to the query.
		$where = $this->compile_where_clause( $user_id, $blog_id, $statuses, $date_from, $date_to );
		if ( ! empty( $where ) ) {
			$query .= ' WHERE ' . implode( ' AND ', $where );
		}

		// Order by the create date.
		$query .= ' ORDER BY Reports.create_date DESC';

		// Add the limit and offset.
		$query .= " LIMIT {$limit} OFFSET {$offset}";

		// Get the reports.
		$reports = $wpdb->get_results( $query ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, cant prepare table names.

		// Map the reports.
		return array_map(
			function ( \stdClass $row ): array {
				// Replace report_id with report_ids.
				$row->report_id = $row->report_ids;
				unset( $row->report_ids );

				return array(
					'report' => $this->map_row_to_report( $row ),
					'logs'   => null !== $row->logs ? (int) $row->logs : 0,
				);},
			$reports
		);
	}

	/**
	 * Get the total count of reports.
	 *
	 * @since 1.0.0
	 *
	 * @param integer|null $user_id   The user id to filter by.
	 * @param integer|null $blog_id   The blog id to filter by.
	 * @param string[]     $statuses  The statuses to filter by.
	 * @param string|null  $date_from The date to filter from.
	 * @param string|null  $date_to   The date to filter to.
	 *
	 * @return integer
	 */
	public function get_total_count(
		?int $user_id = null,
		?int $blog_id = null,
		array $statuses = array(),
		?string $date_from = null,
		?string $date_to = null
	): int {
		global $wpdb;

		// Build the query.
		$query = "SELECT COUNT(*) FROM {$this->report_table_name()}";
		// If we have a where clause, add it to the query.
		$where = $this->compile_where_clause( $user_id, $blog_id, $statuses, $date_from, $date_to );
		if ( ! empty( $where ) ) {
			$query .= ' WHERE ' . implode( ' AND ', $where );
		}

		// Get the count.
		return (int) $wpdb->get_var( $query ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, cant prepare table names.
	}


	/**
	 * Compiles the Where clause for the query.
	 *
	 * @since 1.0.0
	 *
	 * @param integer|null $user_id   The user id to filter by.
	 * @param integer|null $blog_id   The blog id to filter by.
	 * @param string[]     $statuses  The statuses to filter by.
	 * @param string|null  $date_from The date to filter from.
	 * @param string|null  $date_to   The date to filter to.
	 *
	 * @return string[]
	 */
	private function compile_where_clause(
		?int $user_id = null,
		?int $blog_id = null,
		array $statuses = array(),
		?string $date_from = null,
		?string $date_to = null
	): array {
			global $wpdb;

			// Build the where clause.
			$where = array();

			// If we have a user id, add it to the where clause.
		if ( null !== $user_id ) {
			$where[] = $wpdb->prepare( 'user_id = %d', $user_id );
		}

			// If we have a blog id, add it to the where clause.
		if ( null !== $blog_id ) {
			$where[] = $wpdb->prepare( 'blog_id = %d', $blog_id );
		}

			// If we have a status, add it to the where clause.
		if ( ! empty( $statuses ) ) {
			$status  = array_map(
				fn ( string $status ): string => $wpdb->prepare( '%s', $status ),
				$statuses
			);
			$where[] = 'process IN (' . implode( ',', $status ) . ')';
		}

			// If we have a date from, add it to the where clause.
		if ( null !== $date_from && '' !== $date_from ) {
			// Cast date from from yyyy-mm-dd to DateTimeImmutable.
			$date_from = \DateTimeImmutable::createFromFormat( 'Y-m-d', $date_from );

			// If we have a valid date, add it to the where clause.
			if ( $date_from instanceof \DateTimeImmutable ) {
				$where[] = $wpdb->prepare( 'create_date >= %s', $date_from->format( 'Y-m-d H:i:s' ) );
			}
		}

			// If we have a date to, add it to the where clause.
		if ( null !== $date_to && '' !== $date_to ) {
			// Cast date to from dd-mm-yyyy to DateTimeImmutable.
			$date_to = \DateTimeImmutable::createFromFormat( 'Y-m-d', $date_to );

			if ( $date_to instanceof \DateTimeImmutable ) {
				$where[] = $wpdb->prepare( 'create_date <= %s', $date_to->format( 'Y-m-d H:i:s' ) );
			}
		}
		return $where;
	}

	/**
	 * Find all reports based on a post id.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $post_id The post id to find reports for.
	 *
	 * @return array{report: Report, logs: Log[]}
	 */
	public function find_by_post_id( int $post_id ): array {

		// Find all logs which cover this post.
		$logs = $this->find_logs_by_post_id( $post_id );

		return array_reduce(
			$logs,
			/**
			 * Compiles the reports and logs.
			 *
			 * @param array{report: Report, logs: Log[]} $carry The carry.
			 * @param Log                                $log   The log.
			 *
			 * @return array{report: Report, logs: Log[]}
			 */
			function ( array $carry, Log $log ): array {

				if ( ! \array_key_exists( $log->get_report_id(), $carry ) ) {
					$report = $this->find( $log->get_report_id() );
					if ( null !== $report ) {
						$carry[ $log->get_report_id() ] = array(
							'report' => $report,
							'logs'   => array(),
						);
					} else {
						// If the report doesnt exist, skip.
						return $carry;
					}
				}

				// Add the log to the report.
				$carry[ $log->get_report_id() ]['logs'][] = $log;

				return $carry;
			},
			array()
		);
	}

	/**
	 * Get all logs based on a post id.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $post_id The post id to find logs for.
	 *
	 * @return array<int, Log>
	 */
	public function find_logs_by_post_id( int $post_id ): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->log_table_name()} WHERE post_id = %d", //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, cant prepare table names.
				$post_id
			)
		);

		return array_map(
			function ( \stdClass $row ): Log {
				return new Log(
					$row->id,
					$row->report_id,
					$row->post_id,
					$row->links
				);
			},
			$rows
		);
	}

	/**
	 * Delete a report and its logs.
	 *
	 * @since 1.0.0
	 *
	 * @param string $report_id The report id to delete.
	 *
	 * @return boolean
	 *
	 * @throws \Exception If there is an error deleting the report.
	 */
	public function delete_report( string $report_id ): bool {
		// Get the report.
		$report = $this->find_by_report_id( $report_id );

		// If we have no report, throw an exception.
		if ( null === $report ) {
			throw new \Exception( esc_html__( 'Report not found.', 'wpcomsp_wayback_link_fixer' ) );
		}

		global $wpdb;

		// Get all logs for report.
		$logs = $this->get_logs( $report );

		// Delete all logs.
		foreach ( $logs as $log ) {
			$this->delete_log( $log );
		}

		return $wpdb->delete(
			$this->report_table_name(),
			array(
				'report_id' => $report_id,
			),
			array(
				'%s',
			)
		);
	}

	/**
	 * Delete a log.
	 *
	 * @since 1.0.0
	 *
	 * @param Log $log The log to delete.
	 *
	 * @return boolean
	 *
	 * @throws \Exception If there is an error deleting the log.
	 */
	public function delete_log( Log $log ): bool {
		global $wpdb;

		return $wpdb->delete(
			$this->log_table_name(),
			array(
				'id' => $log->get_id(),
			),
			array(
				'%d',
			)
		);
	}
}
