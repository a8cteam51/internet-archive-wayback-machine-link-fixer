<?php

/**
 * Handles the registration and rendering of the report page.
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Dashboard;

use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;
use Internet_Archive\Wayback_Machine_Link_Fixer\Report\Report_Table;
use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link_Repository;
use Internet_Archive\Wayback_Machine_Link_Fixer\Dashboard\Dashboard_Page;

defined( 'ABSPATH' ) || exit;

/**
 * The report page.
 */
class Report_Page {

	/**
	 * The page slug.
	 */
	private const SLUG = 'iawmlf-links';

	/**
	 * The link repository.
	 *
	 * @var Link_Repository
	 */
	private $link_repository;

	/**
	 * Holds the current page hook.
	 *
	 * @var string
	 */
	private $hook;

	/**
	 * Creates a new instance of the report page.
	 */
	public function __construct() {
		$this->link_repository = new Link_Repository();
	}

	/**
	 * Generate the page url.
	 *
	 * @since 1.2.0
	 *
	 * @return string
	 */
	public static function get_page_url(): string {
		return admin_url( 'admin.php?page=' . self::SLUG );
	}

	/**
	 * Register all hooks.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function initialize(): void {

		add_action( 'admin_menu', array( $this, 'register_page' ) );

		add_filter(
			'set-screen-option',
			function ( $status, $option, $value ) {
				return ( 'links_per_page' === $option ) ? (int) $value : $status;
			},
			10,
			3
		);
	}

	/**
	 * Register the report page.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function register_page(): void {
		$hook = add_submenu_page(
			Dashboard_Page::DASHBOARD_SLUG,
			__( 'Wayback Link Fixer - Links', 'internet-archive-wayback-machine-link-fixer' ),
			__( 'Links', 'internet-archive-wayback-machine-link-fixer' ),
			'manage_options',
			self::SLUG,
			array( $this, 'render_page' )
		);

		$this->hook = $hook;

		// Handle the link details form submission.
		add_action( "load-$hook", array( $this, 'handle_link_details_form' ) );

		// Add the screen options.
		add_action( "load-$hook", array( $this, 'register_screen_options' ) );

		// Toggle bulk actions.
		add_filter( 'bulk_actions-' . $hook, array( $this, 'add_bulk_actions' ) );

		// Enqueue the scripts and styles.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue the scripts and styles for the page.
	 *
	 * @since 1.3.0
	 *
	 * @param string $hook The current page hook.
	 *
	 * @return void
	 */
	public function enqueue_scripts( string $hook ): void {
		// If not this page, bail.
		if ( $hook !== $this->hook ) {
			return;
		}

		// Enqueue the admin styles.
		wp_enqueue_style(
			self::SLUG,
			IAWMLF_URL . 'assets/css/build/style-style.scss.css',
			array(),
			IAWMLF_VERSION
		);

		// Register the admin scripts.
		wp_register_script(
			self::SLUG,
			IAWMLF_URL . 'assets/js/build/link-table.js',
			array(),
			IAWMLF_VERSION,
			true
		);

		// Localize the script with exclusion confirm messages.
		wp_localize_script(
			self::SLUG,
			'iawmlf_link_table',
			array(
				'confirmExclude' => __( 'Are you sure you want to exclude this link? It will no longer be shown in the list, it will no longer be checked, and will not be fixed if broken.', 'internet-archive-wayback-machine-link-fixer' ),
				'confirmInclude' => __( 'Are you sure you want to include this link? It will be checked and fixed if broken.', 'internet-archive-wayback-machine-link-fixer' ),
			)
		);

		// Enqueue the admin scripts.
		wp_enqueue_script( self::SLUG );
	}

	/**
	 * Add the bulk actions.
	 *
	 * @since 1.2.0
	 *
	 * @param array $actions The current actions.
	 *
	 * @return array
	 */
	public function add_bulk_actions( array $actions ): array {
		$is_online = Settings::is_archive_api_online();
		// If the API is not online, make all options disabled.
		if ( ! $is_online ) {
			$actions = array(
				'no-op' => __( 'API Offline, options disabled', 'internet-archive-wayback-machine-link-fixer' ),
			);
			return $actions;
		}

		return $actions;
	}

	/**
	 * Render the report page.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function render_page(): void {
		// If we have the link id in the query string, render the single report page.
		if ( isset( $_GET['iawmlf_link_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended, Can be linked, so no nonce possible.
			$this->render_single_page();
			return;
		}

		// Otherwise, render the list page.
		$this->render_list_page();
	}

	/**
	 * Register the screen options for the list table.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function register_screen_options(): void {
		// If we viewing a single link, do not show the screen options.
		if ( isset( $_GET['iawmlf_link_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended, Can be linked, so no nonce possible.
			return;
		}

		// Links per page option.
		$option = 'per_page';
		$args   = array(
			'label'   => __( 'Links', 'internet-archive-wayback-machine-link-fixer' ),
			'default' => 20,
			'option'  => 'links_per_page',
		);

		add_screen_option( $option, $args );

		// Add the screen help tab.
		$screen = get_current_screen();
		$screen->add_help_tab(
			array(
				'id'       => 'iawmlf_help_bulk_actions',
				'title'    => __( 'Bulk Actions', 'internet-archive-wayback-machine-link-fixer' ),
				'callback' => array( $this, 'render_help_bulk_actions' ),
			)
		);
		$screen->add_help_tab(
			array(
				'id'       => 'iawmlf_help_table_columns',
				'title'    => __( 'Table Columns', 'internet-archive-wayback-machine-link-fixer' ),
				'callback' => array( $this, 'render_help_columns' ),
			)
		);
	}

	/**
	 * Render the help tab for Bulk Actions
	 *
	 * @since 1.3.0
	 *
	 * @param \WP_Screen $screen The current screen.
	 * @param array      $args   The arguments passed to the callback.
	 *
	 * @return void
	 */
	public function render_help_bulk_actions( \WP_Screen $screen, array $args ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		iawmlf_render_template( 'admin/links-table/help-tab-bulk-actions.php' );
	}

	/**
	 * Render the help tab for Table Columns.
	 *
	 *  @since 1.3.0
	 *
	 * @param \WP_Screen $screen The current screen.
	 * @param array      $args   The arguments passed to the callback.
	 *
	 * @return void
	 */
	public function render_help_columns( \WP_Screen $screen, array $args ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		iawmlf_render_template( 'admin/links-table/help-tab-columns.php' );
	}

	/**
	 * Render the report list page.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	private function render_list_page(): void {
		// Render the list table.
		$table = new Report_Table( new Link_Repository() );

		// Run the bulk actions.
		$table->process_bulk_action();

		// Render any notices.
		$table->render_notices();

		// Get the current page.
		$current_page = isset( $_REQUEST['page'] ) ? \sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) : self::SLUG; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, Can be linked, so no nonce possible.

		echo '<div class="wrap">';
		printf(
			'<h1 class="wp-heading-inline">%s</h1>',
			esc_html__( 'Wayback Link Fixer - Links', 'internet-archive-wayback-machine-link-fixer' ),
		);

		echo '<hr class="wp-header-end">';

		// If we have a post id in params, show a message.
		if ( array_key_exists( 'iawmlf_filtered_post_id', $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended, Can be linked, so no nonce possible.
			$post_id = absint( wp_unslash( $_GET['iawmlf_filtered_post_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, Can be linked, so no nonce possible.

			// Get the post title.
			$post = get_post( $post_id );

			// Show if we have a valid post.
			if ( $post ) {
				$body = sprintf(
					// translators: %1$s is the post title, %2$s is the link to view all links.
					__( 'Showing links for %1$s <a href="%2$s">(Show all links)</a>', 'internet-archive-wayback-machine-link-fixer' ),
					esc_html( $post->post_title ),
					esc_url( self::get_page_url() ?? '' )
				);

				printf(
					// translators: %s is the body of the message.
					'<div class="notice notice-info"><p>%s</p></div>',
					wp_kses( $body, array( 'a' => array( 'href' => array() ) ) )
				);
			}
		}

		// Render the table.
		$table->prepare_items();
		echo '<form method="get">';
		echo '<input type="hidden" name="page" value="' . esc_attr( $current_page ) . '">'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, Can be linked, so no nonce possible.
		$table->search_box( __( 'Search', 'internet-archive-wayback-machine-link-fixer' ), 'iawmlf-link-search' );
		$table->display();
		echo '</form>';

		echo '</div>';
	}

	/**
	 * Render the single page.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	private function render_single_page(): void {

		// Get the link.
		$link_id = isset( $_GET['iawmlf_link_id'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended, Can be linked, so no nonce possible.
			? absint( wp_unslash( $_GET['iawmlf_link_id'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended, Can be linked, so no nonce possible
			: 0;

		// If the link does not exist, show an error.
		if ( 0 === $link_id ) {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'The link does not exist.', 'internet-archive-wayback-machine-link-fixer' )
			);
			return;
		}

		// Get the link from the repository.
		$link = $this->link_repository->find_by_id( $link_id );

		// If the link is not valid, show an error.
		if ( ! $link ) {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'The link is not valid.', 'internet-archive-wayback-machine-link-fixer' )
			);
			return;
		}
		// Show success notice if we just updated the link.
		if ( ! empty( $_GET['iawmlf_updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended, Redirect param, no nonce possible.
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html__( 'Link updated successfully.', 'internet-archive-wayback-machine-link-fixer' )
			);
		}

		// Render the template.
		iawmlf_render_template(
			'admin/reports/link-details.php',
			array(
				'iawmlf_link'     => $link,
				'iawmlf_posts'    => array_map( 'get_post', array_unique( $this->link_repository->get_post_ids_from_link_id( $link->get_id() ) ) ),
				'iawmlf_back_url' => wp_get_referer() ?: self::get_page_url(), // phpcs:ignore Universal.Operators.DisallowShortTernary.Found, returns false, so cant use ??
			)
		);
	}

	/**
	 * Handle the link details form submission.
	 *
	 * @since 1.4.0
	 *
	 * @return void
	 */
	public function handle_link_details_form(): void {
		// Bail if this is not a link details form submission.
		if ( ! isset( $_POST['iawmlf_link_details_nonce'] ) ) {
			return;
		}

		// Verify the nonce.
		check_admin_referer( 'iawmlf_link_details', 'iawmlf_link_details_nonce' );

		// Get and validate the link ID.
		$link_id = isset( $_POST['iawmlf_link_id'] ) ? absint( wp_unslash( $_POST['iawmlf_link_id'] ) ) : 0;
		if ( 0 === $link_id ) {
			return;
		}

		// Get the link from the repository.
		$link = $this->link_repository->find_by_id( $link_id );
		if ( ! $link ) {
			return;
		}

		// Determine the exclusion state from the checkbox.
		$exclude = isset( $_POST['iawmlf_exclude_link'] );

		if ( $exclude ) {
			$link->set_excluded( true );

			// Set message if not already set.
			if ( '' === $link->get_message() ) {
				$user = wp_get_current_user();
				$link->set_message(
					sprintf(
						'User Requested To Exclude (%1$s on %2$s)',
						$user->user_login,
						wp_date( get_option( 'date_format' ) )
					)
				);
			}
		} else {
			$link->set_excluded( false );

			// Clear message only if it was set by a user exclusion request.
			if ( 0 === strpos( $link->get_message(), 'User Requested To Exclude' ) ) {
				$link->set_message( '' );
			}
		}

		// Allow 3rd parties to hook in and modify the link before saving.
		$link = apply_filters( 'iawmlf_before_saving_link_details', $link );

		// Save the link.
		$this->link_repository->upsert( $link );

		// Allow 3rd parties to hook in and modify the redirect param after saving, for showing custom notices.
		$has_updated = (bool) apply_filters( 'iawmlf_link_details_updated_redirect_param', '1', $link );

		// Redirect back to the link details page with a success notice.
		$redirect_url = add_query_arg(
			array(
				'page'           => self::SLUG,
				'iawmlf_link_id' => $link_id,
				'iawmlf_updated' => $has_updated ? '1' : '',
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}
}
