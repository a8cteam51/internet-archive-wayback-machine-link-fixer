<?php

/**
 * Meta Box Runner
 *
 * @since      1.0.0
 * @version    1.0.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Analyzer\Runner;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Runner\Runner;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Reports;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report_Helper;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report_Repository;

defined( 'ABSPATH' ) || exit;

/**
 * Meta Box Runner
 */
class Meta_Box_Runner {

	public const NONCE_ACTION = 'wpcomsp_wayback_link_fixer_meta_box_runner';
	public const AJAX_HANDLE  = 'wlf_meta_box_runner';

	/**
	 * Notices which should be rendered as either PHP or Editor notices
	 *
	 * @var array<int, array{message:string, type:string}> $notices
	 */
	private array $notices = array();

	/**
	 * Access to the report repository.
	 *
	 * @var \WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report_Repository
	 */
	private Report_Repository $report_repository;

	/**
	 * Access to the reports factory.
	 *
	 * @var \WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Reports
	 */
	private Reports $reports;

	/**
	 * Initialise the settings page.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function initialize(): void {
		// dump( \DOING_AJAX );
		// If not on edit.php in the admin, return.
		if ( \is_admin() && 'post.php' === \basename( $_SERVER['PHP_SELF'] ) ) {
			$this->register_hooks();
		}

		// If doing ajax
		if ( wp_doing_ajax() ) {
			$this->register_hooks();
		}
	}

	/**
	 * Register all hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function register_hooks(): void {

		// Create instance of Report Repository.
		$this->report_repository = new Report_Repository();
		$this->reports           = new Reports();

		\add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
		\add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		\add_action( 'wp_ajax_' . self::AJAX_HANDLE, array( $this, 'ajax_callback' ) );
		\add_action( 'wp_ajax_nopriv_' . self::AJAX_HANDLE, array( $this, 'ajax_callback' ) );
	}

	/**
	 * Checks if this current view should render a meta box.
	 *
	 * @since   1.0.0
	 *
	 * @return  boolean
	 */
	private function should_render_meta_box(): bool {
		return in_array( \get_post_type(), Settings::get_post_types(), true );
	}


	/**
	 * Register the meta box.
	 *
	 * @since   1.0.0
	 *
	 * @return  void
	 */
	public function register_meta_box(): void {
		if ( ! $this->should_render_meta_box() ) {
			return;
		}
		\add_meta_box(
			'wpcomsp-wayback-link-fixer',
			__( 'Wayback Link Fixer', 'wpcomsp_wayback_link_fixer' ),
			array( $this, 'render_meta_box' ),
			null,
			'side',
			'high'
		);
	}

	/**
	 * Render the meta box.
	 *
	 * @since   1.0.0
	 *
	 * @param \WP_Post                                                        $post The post object.
	 * @param array{id:string, title:string, callback:callable, args:mixed[]} $args The arguments.
	 *
	 * @return  void
	 */
	public function render_meta_box( \WP_Post $post, array $args ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		wpcomsp_wayback_link_fixer_render_template(
			'admin/meta-box/meta-box-runner.php',
			array(
				'post'    => $post,
				'reports' => $this->report_repository->find_by_post_id( $post->ID ),
			)
		);
	}

	/**
	 * Enqueue the scripts.
	 *
	 * @since   1.0.0
	 *
	 * @return  void
	 */
	public function enqueue_scripts(): void {
		if ( ! $this->should_render_meta_box() ) {
			return;
		}
		//  Enqueue the styles.
		wp_enqueue_style(
			'meta-box-runner',
			WPCOMSP_WAYBACK_LINK_FIXER_URL . 'assets/css/build/style-style.scss.css',
			array(),
			WPCOMSP_WAYBACK_LINK_FIXER_METADATA['Version']
		);
		// Enqueue the scripts.
		wp_register_script(
			'meta-box-runner',
			WPCOMSP_WAYBACK_LINK_FIXER_URL . 'assets/js/build/admin-meta-box.js',
			array( 'jquery' ),
			WPCOMSP_WAYBACK_LINK_FIXER_METADATA['Version'],
			true
		);
		\wp_localize_script(
			'meta-box-runner',
			'reportRunner',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'action'  => self::AJAX_HANDLE,
				'nonce'   => wp_create_nonce( self::NONCE_ACTION ),
				'postId'  => get_the_ID(),
			)
		);

		wp_enqueue_script( 'meta-box-runner' );
	}

	/**
	 * The Ajax callback handler.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function ajax_callback(): void {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], self::NONCE_ACTION ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce.' ), 403 );
		}

		// Get the post id from the request.
		if ( ! isset( $_POST['postId'] ) ) {
			wp_send_json_error( array( 'message' => 'No post id provided.' ), 400 );
		}
		$post_id = (int) $_POST['postId'];

		// Get the list of http codes from the request.
		if ( ! isset( $_POST['httpCodes'] ) ) {
			wp_send_json_error( array( 'message' => 'No http codes provided.' ), 400 );
		}
		$codes = \sanitize_text_field( $_POST['httpCodes'] );

		// Get the ignore cache value from the request.
		$ignore_cache = isset( $_POST['ignoreCache'] ) && '1' === $_POST['ignoreCache'];

		$report = $this->run_report( $post_id, $ignore_cache, $codes );
		wp_send_json_success(
			array(
				'details'     => array(
					'postId'      => $post_id,
					'ignoreCache' => $ignore_cache,
					'httpCodes'   => $codes,
					'reportLink'  => Report_Helper::get_single_report_link( $report ),
				),
				'reportsHTML' => wpcomsp_wayback_link_fixer_render_template(
					'admin/meta-box/meta-box-report-list.php',
					array( 'reports' => $this->report_repository->find_by_post_id( $post_id ) ),
					false
				),
				'message'     => __( 'Your report is ready', 'wpcomsp_wayback_link_fixer' ),
			),
			200
		);
	}

	/**
	 * Run the report.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $post_id      The post id.
	 * @param boolean $ignore_cache Whether to ignore the cache.
	 * @param string  $http_codes   The http codes to check.
	 *
	 * @return Report|null
	 */
	private function run_report( int $post_id, bool $ignore_cache, string $http_codes ): ?Report {

		// Compile the report message.
		$report_message = esc_html(
			\sprintf(
				// Translators: %1$s is the post id, %2$s is the ignore cache value, %3$s is the http codes.
				'Running report for post %d with ignore_cache: %s and http_codes: %s',
				$post_id,
				$ignore_cache ? 'true' : 'false',
				$http_codes
			)
		);

		try {
			$report = Runner::from_post_id( $post_id, $ignore_cache, $http_codes )->run(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			$report = $this->reports->add_description_to_report( $report, $report_message );
			$report = $this->reports->mark_report_as_completed( $report );
		} catch ( \Throwable $th ) {
			wp_send_json_error( array( 'message' => $th->getMessage() ), 400 );
			return null;
		}

		return $report;
	}
}
