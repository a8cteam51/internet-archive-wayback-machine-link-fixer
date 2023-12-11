<?php

/**
 * Scan Command
 *
 * @since      1.0.0
 * @version    1.0.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\CLI;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report_Helper;
use WPCOMSpecialProjects\Wayback_Link_Fixer\CSV\Report_CSV_Generator;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Analyzer\Content_Analyzer;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Analyzer\Runner\CLI_Runner;

/**
 * Scan Command
 *
 * @template Args as array{
 *  dry-run: bool,
 *  post-types: string[],
 *  http-status: string,
 *  ignore-cache: bool,
 *  ignore-posts: int[],
 *  create-csv: bool,
 *  blog: int,
 *  }
 */
class Scan_Command {

	public const COMMAND = 'wlf_scan';

	/**
	 * The Runner.
	 *
	 * @since 1.0.0
	 *
	 * @var CLI_Runner
	 */
	private CLI_Runner $runner;

	/**
	 * The command args.
	 *
	 * @since 1.0.0
	 *
	 * @var Args
	 */
	private array $args;

	/**
	 * Register all hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'cli_init', array( $this, 'register_command' ) );
	}

	/**
	 * Register the command if WP CLI is available.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_command(): void {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( self::COMMAND, $this );
		}
	}

	/**
	 * Invoke the command.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args       The arguments.
	 * @param array $assoc_args The associative arguments.
	 *
	 * @return void
	 */
	public function __invoke( array $args, array $assoc_args ) {
		$this->args = $this->get_arguments( $assoc_args );

		// Display the args passed.
		$this->generate_initial_output();

		// If doing dry run.
		if ( $this->args['dry-run'] ) {
			\WP_CLI::line( '------' );
			\WP_CLI::line( 'Running in dry run mode, exiting' );
			\WP_CLI::line( '------' );
			return;
		}

		$results = $this->run_scan();

		// Output the results.
		\WP_CLI::line( '------' );
		\WP_CLI::line( 'Scan Results' );
		foreach ( $results as $wlf_id => $wlf_result ) {
			if ( false === $wlf_result ) {
				\WP_CLI::error( 'Blog ID: #' . $wlf_id . ' failed' );
			} else {
				\WP_CLI::success( 'Blog ID: #' . $wlf_id . ' succeeded' );
			}
		}
	}

	/**
	 * Generate the intitial output of all commands.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function generate_initial_output(): void {
		\WP_CLI::log( 'Starting scan...' );
		\WP_CLI::log( 'Dry run: ' . ( $this->args['dry-run'] ? 'true' : 'false' ) );
		\WP_CLI::log( 'Post types: ' . implode( ',', $this->args['post-types'] ) );
		\WP_CLI::log( 'HTTP status codes: ' . $this->args['http-status'] );
		\WP_CLI::log( 'Ignore cache: ' . ( $this->args['ignore-cache'] ? 'true' : 'false' ) );
		\WP_CLI::log( 'Ignore posts: ' . implode( ',', $this->args['ignore-posts'] ) );
		\WP_CLI::log( 'Create CSV: ' . ( $this->args['create-csv'] ? 'true' : 'false' ) );
		\WP_CLI::log( 'Blog ID: ' . ( 0 === $this->args['blog-id'] ? 'All' : $this->args['blog-id'] ) );
	}

	/**
	 * Get the command arguments.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, string> $assoc_args The associative arguments.
	 *
	 * @return Args
	 */
	public function get_arguments( array $assoc_args ): array {
		// Unpack post types if set.
		if ( isset( $assoc_args['post-types'] ) ) {
			$assoc_args['post-types'] = explode( ',', (string) esc_attr( $assoc_args['post-types'] ) );
			$assoc_args['post-types'] = array_map( 'trim', $assoc_args['post-types'] );
		}

		// Unpcak ignore posts if set.
		if ( isset( $assoc_args['ignore-posts'] ) ) {
			$assoc_args['ignore-posts'] = explode( ',', (string) esc_attr( $assoc_args['ignore-posts'] ) );
			$assoc_args['ignore-posts'] = array_map( 'trim', $assoc_args['ignore-posts'] );
			$assoc_args['ignore-posts'] = array_map( 'intval', $assoc_args['ignore-posts'] );
		}

		// If ignore cache is set, turn into a bool.
		if ( isset( $assoc_args['ignore-cache'] ) ) {
			$assoc_args['ignore-cache'] = filter_var( $assoc_args['ignore-cache'], FILTER_VALIDATE_BOOLEAN );
		}

		$wlf_defaults = array(
			'dry-run'      => false,
			'post-types'   => Settings::get_post_types(),
			'http-status'  => Settings::get_http_status_codes(),
			'ignore-cache' => false,
			'ignore-posts' => array(),
			'create-csv'   => false,
			'blog-id'      => 1,
		);

		// If a blog id is set, but its not a multisite, set as 1 and show notice.
		if ( isset( $assoc_args['blog-id'] ) && ! is_multisite() ) {
			\WP_CLI::line( '------' );
			\WP_CLI::line( 'Blog id set, but not a multisite, setting to 1' );
			\WP_CLI::line( '------' );
			$assoc_args['blog-id'] = 1;
		}

		// Remove any arguments that are not in the defaults.
		$assoc_args = array_intersect_key( $assoc_args, $wlf_defaults );

		return wp_parse_args( $assoc_args, $wlf_defaults );
	}

	/**
	 * Runs the scan.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, bool>
	 */
	private function run_scan(): array {

		\WP_CLI::line( '------' );

		$start_time = microtime( true );

		$results = array();

		// Get the blog ids.
		$blog_ids = $this->get_blog_ids( $this->args['blog-id'] );
		// If no blog ids, return false.
		if ( empty( $blog_ids ) ) {
			throw new \Exception( 'No blog ids supplied', 1 );
		}

		// Loop through the blog ids.
		foreach ( $blog_ids as $blog_id ) {
			// Run the scan for the blog.
			$results[ $blog_id ] = $this->run_scan_for_blog( $blog_id );
		}

		$end_time = microtime( true );

		\WP_CLI::line( 'Scan completed in ' . round( ( $end_time - $start_time ), 3 ) . ' seconds' );
		\WP_CLI::line( '------' );

		return $results;
	}

	/**
	 * Runs the scan for a given blog id.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $blog_id The blog id.
	 *
	 * @return boolean
	 */
	private function run_scan_for_blog( int $blog_id ): bool {

		// If a multisite, swtich to the blog.
		if ( is_multisite() ) {
			\WP_CLI::line( '------' );
			\WP_CLI::line( 'Switching to blog id: ' . $blog_id );
			\WP_CLI::line( '------' );

			// Cache the current blog.
			$current_blog = get_current_blog_id();

			// Switch to the blog.
			switch_to_blog( $blog_id );
		} else {
			$blog_id = 1;
		}

		// Log the output.
		\WP_CLI::line( 'Running scan for blog id: ' . $blog_id );

		// Create the runner.
		$wlf_runner = new CLI_Runner(
			$this->args['post-types'],
			$this->args['http-status'],
			$this->args['ignore-cache'],
			$this->args['ignore-posts'],
			$this->args['create-csv'],
			$blog_id
		);
		$wlf_runner->setup();

		// Get the total post count from runner.
		\WP_CLI::line( 'Total posts to process: ' . $wlf_runner->get_total_count() );

		// Show the progress bar.
		$wlf_progress = \WP_CLI\Utils\make_progress_bar( 'Processing posts.', $wlf_runner->get_total_count() );

		// Process the batch based on size of
		while ( $wlf_runner->has_more_posts_to_process() ) {
			$wlf_runner->process_batch( 1 );
			$wlf_progress->tick( 1 );
		}

		$wlf_progress->finish();
		$wlf_runner->finalize();

		// Get the report.
		$wlf_report = $wlf_runner->get_report();

		// Output the report details.
		\WP_CLI::line( '------' );
		\WP_CLI::line( 'Report Generated' );
		\WP_CLI::line( 'Report URL: ' . Report_Helper::get_single_report_link( $wlf_report ) );
		\WP_CLI::line( '------' );

		// If we are generating a csv.
		if ( $this->args['create-csv'] ) {
			// Get the csv.
			\WP_CLI::line( 'Generating CSV' );
			try {
				$csv_generator = new Report_CSV_Generator();
				$csv_path      = $csv_generator->generate( $wlf_report );
			} catch ( \Throwable $th ) {
				// Show the error.
				\WP_CLI::line( 'Error generating CSV' . $th->getMessage() );
				$csv_path = false;
			}

			// If we have valid path, get the assumed url.
			$csv_url = $csv_path && file_exists( $csv_path )
				? Report_Helper::get_report_csv_url( $wlf_report )
				: false;

			\WP_CLI::line(
				true === is_string( $csv_url ) ? 'CSV URL: ' . $csv_url : 'CSV could not be generated.'
			);

			// // phpcs:ignore,  \WP_CLI::line( 'CSV URL: ' . $wlf_csv );
			\WP_CLI::line( '------' );
		}

		// revert to the current blog, if multisite.
		if ( is_multisite() ) {
			\WP_CLI::line( 'Switching back to blog id: ' . $current_blog );
			\WP_CLI::line( '------' );

			// Switch back to the current blog.
			switch_to_blog( $current_blog );
		}

		return true;
	}

	/**
	 * Get the blog ids to run the scan for.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $blog_id The blog id.
	 *
	 * @return integer[]
	 */
	private function get_blog_ids( int $blog_id ): array {

		// If blog id is set, return it.
		if ( 0 !== $blog_id ) {
			return array( $blog_id );
		}

		// If not a multi site.
		if ( ! is_multisite() ) {
			return array( get_current_blog_id() );
		}

		// Get all blog ids.
		$blog_ids = get_sites(
			array(
				'fields' => 'ids',
			)
		);

		// If no blog ids, return empty array.
		if ( empty( $blog_ids ) ) {
			return array();
		}

		// Return the blog ids.
		return $blog_ids;
	}
}
