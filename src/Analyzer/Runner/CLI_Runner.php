<?php

/**
 * The CLI Runner.
 *
 * @since      1.0.0
 * @version    1.0.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Analyzer\Runner;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Event\Event;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Event\Events;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Runner\Runner;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Reports;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Analyzer\Content_Analyzer;

/**
 * The CLI Runner
 */
class CLI_Runner {

	/**
	 * Post types to check.
	 *
	 * @since 1.0.0
	 *
	 * @var string[]
	 */
	private array $post_types;

	/**
	 * HTTP Status to check.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private string $http_status;

	/**
	 * Ignore cache.
	 *
	 * @since 1.0.0
	 *
	 * @var bool
	 */
	private bool $ignore_cache;

	/**
	 * Ignore posts.
	 *
	 * @since 1.0.0
	 *
	 * @var integer[]
	 */
	private array $ignore_posts;

	/**
	 * Create CSV.
	 *
	 * @since 1.0.0
	 *
	 * @var bool
	 */
	private bool $create_csv;

	/**
	 * Blog ID.
	 *
	 * @since 1.0.0
	 *
	 * @var integer
	 */
	private int $blog;

	/**
	 * The user id of the blog admin.
	 *
	 * @since 1.0.0
	 *
	 * @var integer
	 */
	private int $blog_admin_id;

	/**
	 * Array of post ids processed.
	 *
	 * @since 1.0.0
	 *
	 * @var integer[]
	 */
	private array $processed_post_ids = array();

	/**
	 * Get post ids to process.
	 *
	 * @since 1.0.0
	 *
	 * @var integer[]
	 */
	private array $post_ids_to_process = array();

	/**
	 * The Report
	 *
	 * @since 1.0.0
	 *
	 * @var Report|null
	 */
	private ?Report $report = null;

	/**
	 * Access to the report repository.
	 *
	 * @since 1.0.0
	 *
	 * @var Reports
	 */
	private Reports $reports;

	/**
	 * Create a new instance of the CLI Runner.
	 *
	 * @since 1.0.0
	 *
	 * @param array   $post_types   The post types to check.
	 * @param string  $http_status  The HTTP status to check.
	 * @param boolean $ignore_cache Ignore cache.
	 * @param array   $ignore_posts Ignore posts.
	 * @param boolean $create_csv   Create CSV.
	 * @param integer $blog         Blog ID.
	 */
	public function __construct(
		array $post_types,
		string $http_status,
		bool $ignore_cache,
		array $ignore_posts,
		bool $create_csv,
		int $blog
	) {
		add_filter( 'wp_fatal_error_handler_enabled', '__return_false' );
		$this->post_types   = $post_types;
		$this->http_status  = $http_status;
		$this->ignore_cache = $ignore_cache;
		$this->ignore_posts = $ignore_posts;
		$this->create_csv   = $create_csv;
		$this->blog         = $blog;

		$this->reports = new Reports();
	}

	/**
	 * Set the blog admin id.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function set_blog_admin_id(): void {

		$admin_email = \is_multisite()
			? get_blog_option( $this->blog, 'admin_email' )
			: get_option( 'admin_email' );

		// Get the blog admin id.
		$user = get_user_by( 'email', $admin_email );

		$this->blog_admin_id = $user ? $user->ID : 0;
	}

	/**
	 * Sets the post ids to process.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function set_post_ids_to_process(): void {
		// Get all post ids.
		$this->post_ids_to_process = get_posts(
			array(
				'post_type'      => $this->post_types,
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'cache_results'  => false,
				'post__not_in'   => $this->ignore_posts,
			)
		);
	}

	/**
	 * Setup the runner
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function setup(): void {

		// Set the blog admin id.
		$this->set_blog_admin_id();
		// Set the post ids to process.
		$this->set_post_ids_to_process();

		// Create the report.
		$this->report = $this->reports->create_report( $this->blog_admin_id, $this->blog );
		$this->report = $this->reports->mark_report_as_in_progress( $this->report );
		$this->report = $this->reports->add_description_to_report( $this->report, $this->get_report_description() );
	}

	/**
	 * Get the current count of posts processed.
	 *
	 * @since 1.0.0
	 *
	 * @return integer
	 */
	public function get_current_count(): int {
		return count( $this->processed_post_ids );
	}

	/**
	 * Get the total count of posts to process.
	 *
	 * @since 1.0.0
	 *
	 * @return integer
	 */
	public function get_total_count(): int {
		return count( $this->post_ids_to_process );
	}

	/**
	 * Checks if there are more posts to process.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function has_more_posts_to_process(): bool {
		return $this->get_current_count() < $this->get_total_count();
	}

	/**
	 * Process batch
	 *
	 * @since 1.0.0
	 *
	 * @param integer $batch_size The batch size.
	 *
	 * @return void
	 */
	public function process_batch( int $batch_size ): void {
		for ( $wlf_i = 0; $wlf_i < $batch_size; $wlf_i++ ) {
			$wlf_post_id = $this->get_next_post_to_process();
			// If there is no content, break.
			if ( ! $wlf_post_id ) {
				break;
			}

			$wlf_content = get_post_field( 'post_content', $wlf_post_id );

			// Create analyzer.
			$wlf_analyzer = new Content_Analyzer( $wlf_content, $wlf_post_id, $this->ignore_cache );
			$wlf_analyzer->analyze( $this->http_status );

			// Add links to a log for report.
			$this->reports->log_post_for_report(
				$this->report,
				$wlf_post_id,
				$wlf_analyzer->get_links()
			);
		}
	}

	/**
	 * Get the next post to process.
	 *
	 * @since 1.0.0
	 *
	 * @return integer|null
	 */
	public function get_next_post_to_process(): ?int {
		foreach ( $this->post_ids_to_process as $post_id ) {
			if ( ! in_array( $post_id, $this->processed_post_ids, true ) ) {
				// Add to the processed post ids.
				$this->processed_post_ids[] = $post_id;

				return $post_id;
			}
		}
		return null;
	}

	/**
	 * Get the report description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	private function get_report_description(): string {
		// Get the report description.
		$description = sprintf(
			/* translators: %1$s is the HTTP status, %2$s is the post types, %3$s is the ignore cache, %4$s is the ignore posts, %5$s is the create csv, %6$s is the blog id */
			__( 'CLI Runner:: HTTP Status: %1$s, Post Types: %2$s, Ignore Cache: %3$s, Ignore Posts: %4$s, Create CSV: %5$s, Blog ID: %6$s', 'wpcomsp_wayback_link_fixer' ),
			$this->http_status,
			implode( ',', $this->post_types ),
			$this->ignore_cache ? 'Yes' : 'No',
			implode( ',', $this->ignore_posts ),
			$this->create_csv ? 'Yes' : 'No',
			$this->blog
		);

		return $description;
	}

	/**
	 * Get the report.
	 *
	 * @since 1.0.0
	 *
	 * @return Report
	 */
	public function get_report(): Report {
		// If no report, throw exception.
		if ( ! $this->report ) {
			throw new \Exception( 'No report found.' );
		}
		return $this->report;
	}

	/**
	 * Finalize the runner.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function finalize(): void {
		// Mark the report as completed.
		$this->report = $this->reports->mark_report_as_completed( $this->report );
	}
}
