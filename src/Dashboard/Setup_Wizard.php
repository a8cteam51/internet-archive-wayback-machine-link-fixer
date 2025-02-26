<?php

/*
 * Class that handles the setup wizard.
 *
 * @since 1.3.0
 *
 * @package WPCOMSpecialProjects\Wayback_Link_Fixer\Dashboard
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Dashboard;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings_Page;

defined( 'ABSPATH' ) || exit;

/**
 * Setup Wizard class.
 */
class Setup_Wizard {

	private const OPTION_NAME = 'wlf_setup_wizard';
	private const PAGE_SLUG   = 'wayback-link-fixer-setup-wizard';
	private const STEPS       = array(
		'step-1'   => 'step-1.php',
		'step-2'   => 'step-2.php',
		'step-3'   => 'step-3.php',
		'complete' => 'complete.php',
	);

	private $page_hook = '';

	/**
	 * Initialize the dashboard notifications.
	 *
	 * @return void
	 */
	public function initialize(): void {
		add_action( 'admin_menu', array( $this, 'register_setup_wizard' ) );
		add_action( 'admin_init', array( $this, 'render_admin_notice' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Checks if the setup wizard is complete.
	 *
	 * @return boolean
	 */
	public static function is_setup_complete(): bool {
		$state = get_option( self::OPTION_NAME, 'step-1' );
		return 'complete' === $state;
	}

	/**
	 * Render the admin notice.
	 *
	 * @return void
	 */
	public function render_admin_notice(): void {
		if ( self::is_setup_complete() ) {
			return;
		}

		if ( isset( $_GET['page'] ) && self::PAGE_SLUG === $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$message = sprintf(
		// translators: %s is the URL to the setup wizard.
			__( 'The Wayback Link Fixer plugin is almost ready. Please <a href="%s">run the setup wizard</a> to complete the installation.', 'wpcomsp_wayback_link_fixer' ),
			esc_url( admin_url( 'admin.php?page=wayback-link-fixer-setup-wizard' ) )
		);

		add_action(
			'admin_notices',
			function () use ( $message ) {
				printf( '<div class="notice notice-warning is-dismissible"><p>%s</p></div>', $message ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		);
	}

	/**
	 * Registers the setup wizard.
	 *
	 * @return void
	 */
	public function register_setup_wizard(): void {
		$this->page_hook = add_submenu_page(
			Settings_Page::PAGE_SLUG,
			__( 'Setup Wizard', 'wpcomsp_wayback_link_fixer' ),
			__( 'Setup Wizard', 'wpcomsp_wayback_link_fixer' ),
			'manage_options',
			self::PAGE_SLUG,
			function () {
				$this->render_page();
			}
		);

		// Add the form handling.
		add_action( 'load-' . $this->page_hook, array( $this, 'handle_form' ) );
	}

	/**
	 * Add notice to the admin dashboard.
	 *
	 * @param string $message The message to display.
	 * @param string $type    The type of notice.
	 *
	 * @return void
	 */
	public function add_notice( string $message, string $type = 'error' ): void {
		add_action(
			'admin_notices',
			function () use ( $message, $type ) {
				printf(
					'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
					esc_attr( $type ),
					esc_html( $message )
				);
			}
		);
	}

	/**
	 * Enqueue the settings page scripts.
	 *
	 * @since   1.0.0
	 *
	 * @param   string $page_hook The page hook.
	 *
	 * @return  void
	 */
	public function enqueue_scripts( string $page_hook ): void {
		// Only enqueue the scripts on the settings page.
		if ( $this->page_hook !== $page_hook ) {
			return;
		}
		wp_enqueue_script(
			self::PAGE_SLUG,
			WPCOMSP_WAYBACK_LINK_FIXER_URL . 'assets/js/build/wizard.js',
			array(),
			WPCOMSP_WAYBACK_LINK_FIXER_METADATA['Version'],
			true
		);

		//  Register the styles.
		wp_enqueue_style(
			self::PAGE_SLUG,
			WPCOMSP_WAYBACK_LINK_FIXER_URL . 'assets/css/build/style-style.scss.css',
			array(),
			WPCOMSP_WAYBACK_LINK_FIXER_METADATA['Version']
		);
	}

	/**
	 * Handle the form submission.
	 *
	 * @return void
	 */
	public function handle_form(): void {
		// If the post contains 'wlf-action'
		if ( ! isset( $_POST['wlf-action'] ) ) {
			return;
		}

		// verify nonce and referer
		if ( ! check_admin_referer( 'wlf_wizard_nonce', 'wlf_wizard_nonce' ) ) {
			$this->add_notice( 'Nonce verification failed' );
			return;
		}

		// Get the current step.
		$current = sanitize_text_field( wp_unslash( $_POST['wlf-current-step'] ?? '' ) );

		// If current is not set, bail.
		if ( empty( $current ) ) {
			$this->add_notice( 'Current step is not set' );
			return;
		}

		// If the post contains previous-step, handle the previous step.
		if ( isset( $_POST['wlf-previous-step'] ) ) {
			$this->handle_previous_step( $current );
			return;
		}

		if ( 'step-1' === $current ) {
			$this->handle_step_1();
		} elseif ( 'step-2' === $current ) {
			$this->handle_step_2();
		} elseif ( 'step-3' === $current ) {
			$this->handle_step_3();
		}
	}

	/**
	 * Step back to the previous step.
	 *
	 * @param string $current_step The current step.
	 *
	 * @return void
	 */
	private function handle_previous_step( string $current_step ): void {
		// If current step is step-1, then we can't go back.
		if ( 'step-1' === $current_step ) {
			return;
		}

		$index    = array_keys( self::STEPS );
		$previous = array_search( $current_step, $index, true ) - 1;

		// Update the step.
		$previous_step = $index[ $previous ];
		update_option( self::OPTION_NAME, $previous_step );
	}

	/**
	 * Handles step 1 form submission.
	 *
	 * @return void
	 */
	private function handle_step_1(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		// Check we have the 2 api creds.
		if ( ! isset( $_POST['wlf_wizard_archive_access_key'], $_POST['wlf_wizard_archive_secret_key'] ) ) {
			$this->add_notice( 'Missing archive.org credentials', 'error' );
			return;
		}

		$access_key = sanitize_text_field( wp_unslash( $_POST['wlf_wizard_archive_access_key'] ) );
		$secret_key = sanitize_text_field( wp_unslash( $_POST['wlf_wizard_archive_secret_key'] ) );

		// Save the keys.
		update_option( Settings::ARCHIVE_ORG_ACCESS_KEY, $access_key );
		update_option( Settings::ARCHIVE_ORG_SECRET_KEY, $secret_key );

		// Update the step.
		$next = sanitize_text_field( wp_unslash( $_POST['wlf-next-step'] ) );
		update_option( self::OPTION_NAME, $next );
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Handles step 2 form submission.
	 *
	 * @return void
	 */
	private function handle_step_2(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		// Get values from the form.
		$is_active          = isset( $_POST['wlf_wizard_activate_link_fixer'] );
		$allowed_post_types = isset( $_POST['wlf_wizard_post_types'] ) && is_array( $_POST['wlf_wizard_post_types'] )
			? array_map( fn( $type ) => sanitize_text_field( wp_unslash( $type ) ), $_POST['wlf_wizard_post_types'] )
			: array();
		$scan_existing      = isset( $_POST['wlf_wizard_scan_existing_content'] );
		$outcome            = isset( $_POST['wlf_wizard_outcome'] ) ? sanitize_text_field( wp_unslash( $_POST['wlf_wizard_outcome'] ) ) : 'do_nothing';

		// Update all the settings.
		update_option( Settings::PROCESS_LINKS, $is_active );
		update_option( Settings::ALLOWED_POST_TYPES, $allowed_post_types );
		update_option( Settings::SCAN_EXISTING_POSTS, $scan_existing );
		update_option( Settings::FIXER_OPTION, $outcome );

		// Update the step.
		$next = sanitize_text_field( wp_unslash( $_POST['wlf-next-step'] ) );
		update_option( self::OPTION_NAME, $next );
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Handles step 3 form submission.
	 *
	 * @return void
	 */
	private function handle_step_3(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		// Get values from the form.
		$is_active             = isset( $_POST['wlf_wizard_activate_auto_archiver'] );
		$allowed_post_types    = isset( $_POST['wlf_wizard_post_types'] ) && is_array( $_POST['wlf_wizard_post_types'] )
			? array_map( fn( $type ) => sanitize_text_field( wp_unslash( $type ) ), $_POST['wlf_wizard_post_types'] )
			: array();
		$enable_routine_update = isset( $_POST['wlf_wizard_recurring_backup'] );
		$routine_frequency     = isset( $_POST['wlf_wizard_backup_frequency'] ) ? absint( $_POST['wlf_wizard_backup_frequency'] ) : 7;

		// Update all the settings.
		update_option( Settings::ALLOW_OWN_CONTENT_SUBMISSIONS, $is_active );
		update_option( Settings::ALLOWED_OWN_CONTENT_POST_TYPES, $allowed_post_types );
		update_option( Settings::ROUTINELY_UPDATE_WAYBACK_MACHINE, $enable_routine_update );
		update_option( Settings::ROUTINELY_UPDATE_WAYBACK_MACHINE_INTERVAL, $routine_frequency );

		// Update the step.
		$next = sanitize_text_field( wp_unslash( $_POST['wlf-next-step'] ) );
		update_option( self::OPTION_NAME, $next );
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Render the page.
	 *
	 * @return void
	 */
	public function render_page(): void {
		echo '<div class="wrap">';

		$step_data = $this->get_step_data();

		$view_path = sprintf(
			'%1$s%2$s%3$s%2$s%4$s',
			'admin',
			DIRECTORY_SEPARATOR,
			'wizard',
			$step_data['template']
		);

		$view_data = array(
			'step_data'  => $step_data,
			'settings'   => new Settings(),
			'post_types' => $this->get_public_post_types(),
			'header'     => $this->get_page_header( $step_data ),
			'footer'     => $this->get_page_footer( $step_data ),
		);

		echo wpcomsp_wayback_link_fixer_render_template( $view_path, $view_data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		echo '</div>';
	}

	/**
	 * Get all public post types
	 * Exclude attachment and revision post types
	 *
	 * @return array<string>
	 */
	private function get_public_post_types(): array {
		$post_types = get_post_types(
			array(
				'public' => true,
			),
			'objects'
		);

		// Remove attachment and revision post types.
		unset( $post_types['attachment'] );
		unset( $post_types['revision'] );

		return \array_map(
			function ( $post_type ) {
				return $post_type->label;
			},
			$post_types
		);
	}

	/**
	 * Based on the current state, return the next step.
	 *
	 * @return array{template: string, step: string, next: string, progress: array{current: int, total: int}}
	 */
	private function get_step_data(): array {
		$state = get_option( self::OPTION_NAME, 'step-1' );

		// Get the next step, if finished, return the finished template.
		if ( 'finish' === $state ) {
			return 'finish';
		}

		$index      = array_keys( self::STEPS );
		$next_index = array_search( $state, $index, true ) + 1;

		return array(
			'template' => self::STEPS[ $state ],
			'step'     => $state,
			'next'     => $index[ $next_index ],
			'progress' => array(
				'current' => $next_index,
				'total'   => count( $index ) - 1,
			),
		);
	}

	/**
	 * Gets the header template contents.
	 *
	 * @param array $step_data The step data.
	 *
	 * @return string
	 */
	private function get_page_header( array $step_data ): string {
		$view_path = sprintf(
			'%1$s%2$s%3$s%2$s%4$s',
			'admin',
			DIRECTORY_SEPARATOR,
			'wizard',
			'header.php'
		);

		return wpcomsp_wayback_link_fixer_render_template(
			$view_path,
			array(
				'step_data' => $step_data,
			),
			false
		);
	}

	/**
	 * Gets the footer template contents.
	 *
	 * @param array $step_data The step data.
	 *
	 * @return string
	 */
	private function get_page_footer( array $step_data ): string {
		$view_path = sprintf(
			'%1$s%2$s%3$s%2$s%4$s',
			'admin',
			DIRECTORY_SEPARATOR,
			'wizard',
			'footer.php'
		);

		return wpcomsp_wayback_link_fixer_render_template(
			$view_path,
			array(
				'step_data' => $step_data,
			),
			false
		);
	}
}
