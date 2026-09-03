<?php

/**
 * Handles all the table related actions.
 *
 * Registered as one of the Integrations.
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\WP_Post;

use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link;
use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;
use Internet_Archive\Wayback_Machine_Link_Fixer\Dashboard\Report_Page;
use Internet_Archive\Wayback_Machine_Link_Fixer\Dashboard\Settings_Page;
use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link_Repository;

defined( 'ABSPATH' ) || exit;

/**
 * Post table controller
 */
class WP_Post_Table_Controller {

	public const LINK_COLUMN_KEY = 'wayback_links';

	public const ARCHIVED_COLUMN_KEY = 'wayback_archived';

	/**
	 * Cache of links already fetched.
	 *
	 * @var Link[]
	 */
	private $links = array();

	/**
	 * The link repository.
	 *
	 * @var Link_Repository
	 */
	private $link_repository;

	/**
	 * Creates an instance of the class.
	 */
	public function __construct() {
		$this->link_repository = new Link_Repository();
	}

	/**
	 * Register all hooks.
	 *
	 * @return void
	 */
	private function register_hooks(): void {
		// If logged in users is not allowed to view the links, return.
		if ( ! current_user_can( Settings::get_reporting_page_capability() ) ) {
			return;
		}
		add_filter( 'manage_posts_columns', array( $this, 'add_column' ) );
		add_filter( 'manage_pages_columns', array( $this, 'add_column' ) );
		add_action( 'manage_pages_custom_column', array( $this, 'render_link_column' ), 10, 2 );
		add_action( 'manage_posts_custom_column', array( $this, 'render_link_column' ), 10, 2 );
		add_action( 'manage_pages_custom_column', array( $this, 'render_archived_column' ), 10, 2 );
		add_action( 'manage_posts_custom_column', array( $this, 'render_archived_column' ), 10, 2 );
		add_filter( 'default_hidden_columns', array( $this, 'hide_archived_column_by_default' ), 10, 2 );
	}

	/**
	 * Initialize and register all hooks
	 *
	 * @return void
	 */
	public function initialize(): void {
		$this->register_hooks();
	}

	/**
	 * Gets a link based on its ID.
	 *
	 * If it has already been fetched, will grab from the cache.
	 *
	 * @param integer $link_id The link ID.
	 *
	 * @return Link|null
	 */
	private function get_link( int $link_id ): ?Link {
		// Look for the link in the cache
		if ( array_key_last( $this->links ) === $link_id ) {
			return $this->links[ $link_id ];
		}

		// Fetch the link from the repository
		$link = $this->link_repository->find_by_id( $link_id );

		// Add to the cache.
		$this->links[ $link_id ] = $link;

		return $link;
	}

	/**
	 * Register additional column in post table.
	 *
	 * @param array $columns The columns.
	 *
	 * @return array
	 */
	public function add_column( array $columns ): array {
		$post_type = $this->get_current_post_type();

		// Add the links column for any post type whose links are scanned.
		if ( in_array( $post_type, Settings::get_allowed_post_types(), true ) ) {
			$columns[ self::LINK_COLUMN_KEY ] = __( 'Links', 'internet-archive-wayback-machine-link-fixer' );
		}

		// Add the archived column for any post type that gets archived.
		if ( in_array( $post_type, Settings::own_link_allowed_post_types(), true ) ) {
			$columns[ self::ARCHIVED_COLUMN_KEY ] = __( 'Last Archived', 'internet-archive-wayback-machine-link-fixer' );
		}

		return $columns;
	}

	/**
	 * Gets the post type of the table currently being rendered.
	 *
	 * @return string
	 */
	private function get_current_post_type(): string {
		$screen = get_current_screen();
		if ( $screen ) {
			return $screen->post_type;
		}

		// Fall back to the request's post_type or the post being edited.
		return isset( $_REQUEST['post_type'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			? \sanitize_key( $_REQUEST['post_type'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			: 'post';
	}

	/**
	 * Renders the link details for a given post.
	 *
	 * @param string  $column_name The column name.
	 * @param integer $post_id     The post ID.
	 *
	 * @return void
	 */
	public function render_link_column( string $column_name, int $post_id ): void {
		if ( self::LINK_COLUMN_KEY !== $column_name ) {
			return;
		}

		// If the post is excluded, show a message with a link to settings.
		if ( in_array( $post_id, Settings::get_link_fixer_excluded_posts(), true ) ) {
			printf(
				'<a href="%1$s"><em>%2$s</em></a>',
				esc_url( Settings_Page::get_page_url() ),
				esc_html__( 'Excluded post', 'internet-archive-wayback-machine-link-fixer' )
			);
			return;
		}

		// Get the links from the posts meta.
		$links = get_post_meta( $post_id, Settings::LINK_META_KEY, true );

		// If we have no links (empty or not an array), say so rather than leaving the cell blank.
		if ( ! is_array( $links ) || empty( $links ) ) {
			echo esc_html__( 'No links found', 'internet-archive-wayback-machine-link-fixer' );
			return;
		}

		// Get the links.
		$links = array_map( array( $this, 'get_link' ), $links );

		// Remove any null values.
		$links = array_filter( $links );

		// Get the stats.
		$stats = $this->get_stats( $links );

		// If we have no links, show a message.
		if ( empty( $stats['total'] ) ) {
			echo esc_html__( 'No links found', 'internet-archive-wayback-machine-link-fixer' );
			return;
		}

		printf(
			'<a href="%1$s">%2$s</a>',
			esc_url( $this->generate_report_page_url( $links, $post_id ) ),
			wp_kses(
				sprintf(
					/* translators: 1: number of broken links, 2: total number of links */
					__( '%1$s broken out of %2$s', 'internet-archive-wayback-machine-link-fixer' ),
					'<strong>' . esc_html( absint( $stats['broken'] ) ) . '</strong>',
					'<strong>' . esc_html( absint( $stats['total'] ) ) . '</strong>'
				),
				array( 'strong' => array() )
			)
		);
	}

	/**
	 * Hides the archived column until the user opts in via Screen Options.
	 *
	 * Only applies when the user has no saved column preference for the screen,
	 * so an explicit choice is never overridden.
	 *
	 * @param string[]   $hidden Columns hidden by default.
	 * @param \WP_Screen $screen The screen being rendered.
	 *
	 * @return string[]
	 */
	public function hide_archived_column_by_default( array $hidden, $screen ): array {
		if ( ! $screen instanceof \WP_Screen || 'edit' !== $screen->base ) {
			return $hidden;
		}

		if ( ! in_array( $screen->post_type, Settings::own_link_allowed_post_types(), true ) ) {
			return $hidden;
		}

		$hidden[] = self::ARCHIVED_COLUMN_KEY;

		return $hidden;
	}

	/**
	 * Renders the date a post was last archived.
	 *
	 * @param string  $column_name The column name.
	 * @param integer $post_id     The post ID.
	 *
	 * @return void
	 */
	public function render_archived_column( string $column_name, int $post_id ): void {
		if ( self::ARCHIVED_COLUMN_KEY !== $column_name ) {
			return;
		}

		// If the post is excluded from auto archiving, say so rather than showing it as never archived.
		if ( in_array( $post_id, Settings::get_auto_archiver_excluded_posts(), true ) ) {
			printf(
				'<a href="%1$s"><em>%2$s</em></a>',
				esc_url( Settings_Page::get_page_url() ),
				esc_html__( 'Excluded post', 'internet-archive-wayback-machine-link-fixer' )
			);
			return;
		}

		$archived_at = (int) get_post_meta( $post_id, Settings::OWN_LINK_LAST_PROCESSED, true );

		if ( $archived_at <= 0 ) {
			printf( '<em>%s</em>', esc_html__( 'Never archived', 'internet-archive-wayback-machine-link-fixer' ) );
			return;
		}

		printf(
			'<time datetime="%1$s">%2$s</time>',
			esc_attr( gmdate( 'c', $archived_at ) ),
			esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $archived_at ) )
		);
	}

	/**
	 * Get the stats from an array of links.
	 *
	 * @param Link[] $links The links.
	 *
	 * @return array{total: integer, broken: integer}
	 */
	private function get_stats( ?array $links = array() ): array {
		// If we have no links, cast to array.
		$links = $links ?? array();

		$total  = count( $links );
		$broken = count(
			array_filter(
				$links,
				function ( Link $link ): bool {
					return $link->is_broken();
				}
			)
		);

		return compact( 'total', 'broken' );
	}

	/**
	 * Generate the report page url based on the link ids.
	 *
	 * @param array<Link> $links   The links.
	 * @param integer     $post_id The post id.
	 *
	 * @return string
	 */
	private function generate_report_page_url( array $links, int $post_id ): string {
		$url = Report_Page::get_page_url();

		// Add the initial post id.
		$url = add_query_arg( 'iawmlf_filtered_post_id', $post_id, $url );

		return $url;
	}
}
