<?php

/**
 * The main class which is used to run the process for a given post.
 *
 * @since      1.0.0
 * @version    1.0.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Runner;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Analyser\Content_Analyser;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report_Repository;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Reports;

defined( 'ABSPATH' ) || exit;

/**
 * The runner class
 */
class Runner {

	/**
	 * The Report Repository.
	 *
	 * @since 1.0.0
	 *
	 * @var Report_Repository
	 */
	private Report_Repository $report_repository;

	/**
	 * Report factory/helper
	 *
	 * @since 1.0.0
	 *
	 * @var Reports
	 */
	private Reports $reports;

	/**
	 * The post to be processed.
	 *
	 * @since 1.0.0
	 *
	 * @var \WP_Post
	 */
	private \WP_Post $post;

	/**
	 * Ignore Link Cache
	 *
	 * @since 1.0.0
	 *
	 * @var boolean
	 */
	private bool $ignore_link_cache;

	/**
	 * Http codes to look for
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private string $find_http_codes;

	/**
	 * Content Buffer.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private string $content_buffer = '';

	/**
	 * Create instance of Runner.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Post $post              The post to be processed.
	 * @param boolean  $ignore_link_cache Attempt to fix broken links.
	 * @param string   $find_http_codes   HTTP codes to look for.
	 */
	public function __construct( \WP_Post $post, bool $ignore_link_cache, string $find_http_codes ) {
		$this->report_repository = new Report_Repository();
		$this->reports           = new Reports();
		$this->post              = $post;
		$this->ignore_link_cache = $ignore_link_cache;
		$this->find_http_codes   = $find_http_codes;
	}

	/**
	 * Static constructor based on post id.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $post_id           The post id to be processed.
	 * @param boolean $ignore_link_cache Attempt to fix broken links.
	 * @param string  $find_http_codes   HTTP codes to look for.
	 *
	 * @return Runner
	 */
	public static function from_post_id( int $post_id, bool $ignore_link_cache, string $find_http_codes ): Runner {
		return new Runner( \get_post( $post_id ), $ignore_link_cache, $find_http_codes );
	}

	/**
	 * Run the process.
	 *
	 * @since 1.0.0
	 *
	 * @param Report|null $report The report to be updated or null to create a new one.
	 *
	 * @return Report
	 */
	public function run( ?Report $report = null ): Report {
		$wlf_analyser = $this->analyse_content();
		$wlf_links    = $wlf_analyser->get_links();

		$report = $report ?? $this->generate_report();

		// Generate the logs for the post and assign to the report.
		$this->reports->log_post_for_report( $report, $this->post->ID, $wlf_links );

		// Return a fresh version of the report.
		return $this->report_repository->find_by_report_id( $report->get_report_id() );
	}

	/**
	 * Generate the report.
	 *
	 * @since 1.0.0
	 *
	 * @return Report
	 */
	private function generate_report(): Report {
		return $this->reports->create_report();
	}

	/**
	 * Analyse the content.
	 *
	 * @since 1.0.0
	 *
	 * @return Content_Analyser
	 */
	private function analyse_content(): Content_Analyser {
		$analyser = new Content_Analyser( $this->get_content(), ! $this->ignore_link_cache );
		$analyser->analyze( $this->find_http_codes );
		return $analyser;
	}


	/**
	 * Get the content to be processed.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	private function get_content(): string {
		$content = \get_post_field( 'post_content', $this->post->ID );

		if ( ! $content ) {
			$content = '';
		}

		return $content;
	}
}
