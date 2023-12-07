<?php

/**
 * Generates a CSV file based on a report.
 *
 * @since 1.0.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\CSV;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Log;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Link;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report_Helper;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report_Repository;

defined( 'ABSPATH' ) || exit;

/**
 * Report CSV Generator
 */
class Report_CSV_Generator {

	/**
	 * Access to the CSV Writer.
	 *
	 * @since 1.0.0
	 *
	 * @var CSV_Writer
	 */
	private CSV_Writer $csv_writer;

	/**
	 * Access to the Report Repository.
	 *
	 * @since 1.0.0
	 *
	 * @var Report_Repository
	 */
	private Report_Repository $report_repository;

	/**
	 * Creates a new instance of the Report_CSV_Generator.
	 *
	 * @since 1.0.0
	 *
	 * @param string $base_path The base path for the CSV file.
	 *
	 * @return void
	 */
	public function __construct( ?string $base_path = null ) {
		// If no base path is provided, use the uploads dir.
		if ( ! $base_path ) {
			$base_path = wp_upload_dir()['path'];
		}

		$this->csv_writer        = new CSV_Writer( $base_path );
		$this->report_repository = new Report_Repository();
	}

	/**
	 * Writes a CSV file based on a report.
	 *
	 * @since 1.0.0
	 *
	 * @param Report $report The report to generate the CSV file from.
	 *
	 * @return string The path to the generated CSV file.
	 */
	public function generate( Report $report ): string {
		// Get all the logs.
		$logs = $this->get_logs( $report );
		// Set the filename.
		$this->csv_writer->set_filename( Report_Helper::get_report_csv_filename( $report ) );

		// Set the headers.
		$this->csv_writer->set_headers(
			array(
				__( 'report_id', 'wpcomsp_wayback_link_fixer' ),
				__( 'blog_id', 'wpcomsp_wayback_link_fixer' ),
				__( 'user_id', 'wpcomsp_wayback_link_fixer' ),
				__( 'log_id', 'wpcomsp_wayback_link_fixer' ),
				__( 'post_id', 'wpcomsp_wayback_link_fixer' ),
				__( 'href', 'wpcomsp_wayback_link_fixer' ),
				__( 'contents', 'wpcomsp_wayback_link_fixer' ),
				__( 'redirection_target', 'wpcomsp_wayback_link_fixer' ),
				__( 'http_code', 'wpcomsp_wayback_link_fixer' ),
				__( 'replacement_options', 'wpcomsp_wayback_link_fixer' ),
				__( 'comments', 'wpcomsp_wayback_link_fixer' ),
			)
		);

		// Generate the CSV file and return path.
		return $this->csv_writer->generate( $logs );
	}

	/**
	 * Get the logs from a report, for use as rows.
	 *
	 * @param Report $report The report to get the logs from.
	 *
	 * @return array<int, array{
	 *  blog_id: int,
	 *  user_id: int,
	 *  log_id: int,
	 *  post_id: int,
	 *  report_id:
	 *  href:string,
	 *  contents: string,
	 *  redirection_target: string,
	 *  http_code: string,
	 *  replacement_options: string,
	 *  comments: string
	 * }> The logs from the report.
	 */
	private function get_logs( Report $report ): array {
		$compiled = array();
		$logs     = $this->report_repository->get_logs( $report );

		// Iterate through all the logs.
		foreach ( $logs as $log ) {
			$compiled = \array_merge( $compiled, $this->compile_log( $log, $report->get_blog_id(), $report->get_user_id() ) );
		}

		return $compiled;
	}

	/**
	 * Compile a log as an array.
	 *
	 * @param Log     $log     The log to compile.
	 * @param integer $blog_id The blog ID.
	 * @param integer $user_id The user ID.
	 *
	 * @return array<int, array{
	 *  blog_id: int,
	 *  user_id: int,
	 *  log_id: int,
	 *  post_id: int,
	 *  report_id:
	 *  href:string,
	 *  contents: string,
	 *  redirection_target: string,
	 *  http_code: string,
	 *  replacement_options: string,
	 *  comments: string
	 * }>
	 */
	private function compile_log( Log $log, int $blog_id, int $user_id ): array {
		$compiled = array();

		// Create the start of the compiled log.
		$initial = array(
			'report_id' => $log->get_report_id(),
			'blog_id'   => $blog_id,
			'user_id'   => $user_id,
			'log_id'    => $log->get_id(),
			'post_id'   => $log->get_post_id(),
		);

		// Get link and iterate over.
		$links = $this->compile_links( $log->get_links() );

		foreach ( $links as $link ) {
			$compiled[] = array_merge( $initial, $link );
		}

		return $compiled;
	}

	/**
	 * Compile the links from the log as an array.
	 *
	 * @param Link[] $links The links to compile.
	 *
	 * @return array<int, array{
	 *  href:string,
	 *  contents: string,
	 *  redirection_target: string,
	 *  http_code: string,
	 *  replacement_options: string,
	 *  comments: string
	 * }> The compiled links.
	 */
	private function compile_links( array $links ): array {
		$compiled_links = array();

		foreach ( $links as $link ) {
			$compiled_links[] = array(
				'href'                => $link->get_href(),
				'contents'            => $link->get_contents(),
				'redirection_target'  => $link->get_redirect_target(),
				'http_code'           => $link->get_http_code(),
				'replacement_options' => $link->get_replacement_options(),
				'comments'            => $link->get_comments(),
			);
		}

		return $compiled_links;
	}
}
