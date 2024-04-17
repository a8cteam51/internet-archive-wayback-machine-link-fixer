<?php

/**
 * Handles the registration and rendering of the report page.
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Report;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link_Repository;

/**
 * The report page.
 */
class Report_Page {

	/**
	 * The page slug.
	 */
	private const SLUG = 'wayback-link-fixer-report';

	/**
	 * The parent page slug.
	 */
	private const PARENT_SLUG = 'tools.php';

	/**
	 * The link repository.
	 *
	 * @var Link_Repository
	 */
	private $link_repository;

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
		return admin_url( self::PARENT_SLUG . '?page=' . self::SLUG );
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
	}

	/**
	 * Register the report page.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function register_page(): void {
		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Report', 'wpcomsp_wayback_link_fixer' ),
			__( 'Report', 'wpcomsp_wayback_link_fixer' ),
			'manage_options',
			self::SLUG,
			array( $this, 'render_page' )
		);
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
		if ( isset( $_GET['wlf_link_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended, Can be linked, so no nonce possible.
			$this->render_single_page();
			return;
		}

		// Otherwise, render the list page.
		$this->render_list_page();
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

		echo '<div class="wrap">';
		printf(
			'<h1 class="wp-heading-inline">%s</h1>',
			esc_html__( 'Link Reports', 'wpcomsp_wayback_link_fixer' ),
		);

		echo '<hr class="wp-header-end">';

		// If we have a post id in params, show a message.
		if ( array_key_exists( 'wlf_filtered_post_id', $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended, Can be linked, so no nonce possible.
			$post_id = \sanitize_text_field( $_GET['wlf_filtered_post_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, Can be linked, so no nonce possible.

			// Get the post title.
			$post = \get_post( $post_id );

			// Show if we have a valid post.
			if ( $post ) {
				$body = sprintf(
					// translators: %1$s is the post title, %2$s is the link to view all links.
					__( 'Showing links for %1$s <a href="%2$s">(Show all links)</a>', 'wpcomsp_wayback_link_fixer' ),
					esc_html( $post->post_title ),
					esc_url( self::get_page_url() )
				);

				printf(
					// translators: %s is the body of the message.
					'<div class="notice notice-info"><p>%s</p></div>',
					\wp_kses( $body, array( 'a' => array( 'href' => array() ) ) )
				);
			}
		}

		// Render the table.
		$table->prepare_items();
		echo '<form method="get">';
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
		$link_id = absint( $_GET['wlf_link_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, Can be linked, so no nonce possible.

		// If the link does not exist, show an error.
		if ( 0 === $link_id ) {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'The link does not exist.', 'wpcomsp_wayback_link_fixer' )
			);
			return;
		}

		// Get the link from the repository.
		$link = $this->link_repository->find_by_id( $link_id );

		// If the link is not valid, show an error.
		if ( ! $link ) {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'The link is not valid.', 'wpcomsp_wayback_link_fixer' )
			);
			return;
		}
		// Render the template.
		wpcomsp_wayback_link_fixer_render_template(
			'admin/reports/link-details.php',
			array(
				'wlf_link'     => $link,
				'wlf_posts'    => array_map( 'get_post', $this->link_repository->get_post_id_from_link_id( $link->get_id() ) ),
				'wlf_back_url' => wp_get_referer() ?: self::get_page_url(), // phpcs:ignore Universal.Operators.DisallowShortTernary.Found, returns false, so cant use ??
			)
		);
	}
}
