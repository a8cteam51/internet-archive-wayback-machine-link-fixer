<?php

/**
 * Meta Box Runner
 *
 * @since      1.0.0
 * @version    1.0.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Analyser\Runner;

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
		// If not on edit.php in the admin, return.
		if ( ! \is_admin() || 'post.php' !== \basename( $_SERVER['PHP_SELF'] ) ) {
			return;
		}

		// Create instance of Report Repository.
		$this->report_repository = new Report_Repository();
		$this->reports           = new Reports();

		\add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
		\add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		\add_action( 'init', array( $this, 'runner_handler' ), 1 );
		add_action(
			'enqueue_block_editor_assets',
			function () {
				wp_register_script(
					'meta-box-notices',
					WPCOMSP_WAYBACK_LINK_FIXER_URL . 'assets/js/build/test.js',
					array( 'jquery' ),
					WPCOMSP_WAYBACK_LINK_FIXER_METADATA['Version'],
					true
				);
				\wp_localize_script(
					'meta-box-notices',
					'reportRunner',
					array(
						'notices' => $this->notices,
					)
				);
				wp_enqueue_script( 'meta-box-notices' );
			}
		);
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
		wp_enqueue_script(
			'meta-box-runner',
			WPCOMSP_WAYBACK_LINK_FIXER_URL . 'assets/js/build/admin-meta-box.js',
			array( 'jquery' ),
			WPCOMSP_WAYBACK_LINK_FIXER_METADATA['Version'],
			true
		);
	}

	/**
	 * The handler for the runner
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function runner_handler(): void {
		// If wlf_runner_action is not set in url, bail.
		if ( ! isset( $_GET['wlf_runner_action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		// if wlf_runner_action is not set as "run" bail.
		if ( 'run' !== $_GET['wlf_runner_action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		// If post is not set in url, bail.
		if ( ! isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$ignore_cache = isset( $_GET['ignore_cache'] ) && '1' === $_GET['ignore_cache']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$http_codes   = isset( $_GET['http_codes'] ) ? \sanitize_text_field( $_GET['http_codes'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$report_message = esc_html(
			\sprintf(
				// Translators: %1$s is the post id, %2$s is the ignore cache value, %3$s is the http codes.
				'Running report for post %d with ignore_cache: %s and http_codes: %s',
				absint( $_GET['post'] ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$ignore_cache ? 'true' : 'false',
				$http_codes
			)
		);

		try {
			$runner = Runner::from_post_id( (int) $_GET['post'], $ignore_cache, $http_codes ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$report = $runner->run();

			$report = $this->reports
				->add_description_to_report( $report, $report_message )
				->mark_report_as_completed( $report );
		} catch ( \Throwable $th ) {
			$this->admin_notice( "Failed to process post {$th->getMessage()}", 'error' );
			return;
		}

		// Render notice to say updated with link to access report.
		$this->admin_notice(
			__( 'Created Report.', 'wpcomsp_wayback_link_fixer' ),
			'success',
			Report_Helper::get_single_report_link( $report ),
			__( 'View', 'wpcomsp_wayback_link_fixer' )
		);
	}

	/**
	 * Render a Admin Notice.
	 *
	 * @since 1.0.0
	 *
	 * @param string      $message The message to show.
	 * @param string      $type    The type of notice to show.
	 * @param string|null $link    The link to show.
	 * @param string|null $label   The label to show.
	 *
	 * @return void
	 */
	public function admin_notice( string $message, string $type = 'success', ?string $link = null, ?string $label = null ): void {
		add_action(
			'admin_notices',
			function () use ( $message, $link, $label, $type ) {
				// If we have a label and link, render a link.
				$url = $link && $label
					? sprintf( ' <a href="%s">%s</a>', esc_url( $link ), esc_html( $label ) )
					: '';

				?>
			<div class="notice notice-<?php echo esc_attr( $type ); ?> is-dismissible">
				<p><?php echo esc_html( $message ) . esc_url( $url ); ?></p>
			</div>
				<?php
			}
		);

		// Add to notices array.
		$this->notices[] = array(
			'message' => $message,
			'type'    => $type,
			'url'     => $link,
			'label'   => $label,
		);
	}
}
