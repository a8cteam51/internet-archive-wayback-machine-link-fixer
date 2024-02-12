<?php

/**
 * Main content update service.
 *
 * @since      1.0.0
 * @version    1.0.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Updater;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Log;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Link;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report_Repository;

defined( 'ABSPATH' ) || exit;

/**
 * Updater
 */
class Updater {

	/**
	 * The report which initiated the content writer.
	 *
	 * @var Report
	 */
	private Report $report;

	/**
	 * All logs of the posts in report.
	 *
	 * @var array<Log>
	 */
	private array $logs = array();

	/**
	 * Results from the report.
	 *
	 * @var array<string>
	 */
	private array $results = array();

	/**
	 * Access to the report repository.
	 *
	 * @var Report_Repository
	 */
	private Report_Repository $report_repository;


	/**
	 * Create instance of Content_Writer.
	 *
	 * @since 1.0.0
	 *
	 * @param Report $report The report which initiated the content writer.
	 */
	public function __construct( Report $report ) {
		$this->report_repository = new Report_Repository();
		$this->report            = $report;
		$this->logs              = $this->report_repository->get_logs( $report );
	}

	/**
	 * Run the content writer.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function run(): void {
		// If multisite, switch to the blog.
		if ( is_multisite() ) {
			$original_blog_id = get_current_blog_id();
			switch_to_blog( $this->report->get_blog_id() );
		}

		// If we have no logs, report is done.
		if ( empty( $this->logs ) ) {
			$this->add_result( $this->report->get_id(), __( 'No logs to process', 'wpcomsp_wayback_link_fixer' ) );
		}

		// Get all logs.
		foreach ( $this->logs as $log ) {
			$this->process_log( $log );
		}

		// If multisite, switch back to the original blog.
		if ( is_multisite() ) {
			switch_to_blog( $original_blog_id );
		}
	}

	/**
	 * Gets the results from the content writer.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string>
	 */
	public function get_results(): array {
		return $this->results;
	}

	/**
	 * Add result to the content writer..
	 *
	 * @since 1.0.0
	 *
	 * @param integer $post_id The post id.
	 * @param string  $message The message to add.
	 *
	 * @return void
	 */
	private function add_result( int $post_id, string $message ): void {
		$this->results[ $post_id ] = $message;
	}

	/**
	 * Process logs post.
	 *
	 * @since 1.0.0
	 *
	 * @param Log $log The log to process.
	 *
	 * @return Log
	 */
	private function process_log( Log $log ): Log {

		$processor = new Log_Processor( $log );
		// If we can update the post, process it.
		if ( ! $processor->can_update() ) {
			$this->add_result( $log->get_post_id(), __( 'Can not be processed', 'wpcomsp_wayback_link_fixer' ) );
			return $log;
		}

		// Process the post.
		$result = $processor->update_content();

		// If the post was not processed, add the error message.
		if ( ! $result ) {
			$this->add_result( $log->get_post_id(), __( 'Post was not updated', 'wpcomsp_wayback_link_fixer' ) );
			return $log;
		}

		// Get the updated log and update the report.
		$new_log = $processor->get_log();
		dump(['new log' => $new_log]);
		$this->report_repository->update_log( $new_log );

		// Add the result.
		$this->add_result( $new_log->get_post_id(), __( 'Was updated', 'wpcomsp_wayback_link_fixer' ) );
		return $new_log;
	}
}
