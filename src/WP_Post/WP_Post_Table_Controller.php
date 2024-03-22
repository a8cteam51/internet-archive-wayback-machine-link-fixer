<?php

/**
 * Handles all the table related actions.
 *
 * Registered as one of the Integrations.
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\WP_Post;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link_Repository;

/**
 * Post table controller
 */
class WP_Post_Table_Controller {

	public const LINK_COLUMN_KEY = 'wayback_links';

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
		add_filter( 'manage_pages_columns', array( $this, 'add_column' ) );
		add_filter( 'manage_posts_columns', array( $this, 'add_column' ) );
		add_action( 'manage_posts_custom_column', array( $this, 'render_link_column' ), 10, 2 );
		add_action( 'manage_pages_custom_column', array( $this, 'render_link_column' ), 10, 2 );
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
		if ( \array_key_last( $this->links ) === $link_id ) {
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
		// Get the post type.
		$post_type = \get_post_type();

		// Get the post types from settings.
		$allowed_post_types = Settings::get_post_types();

		// If the post type is not in the allowed post types, return the columns.
		if ( ! \in_array( $post_type, $allowed_post_types, true ) ) {
			return $columns;
		}

		// Add the column.
		$columns[ self::LINK_COLUMN_KEY ] = __( 'Links', 'wpcomsp_wayback_link_fixer' );

		return $columns;
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

		// Get the links from the posts meta.
		$links = get_post_meta( $post_id, Settings::LINK_META_KEY, true );

		// If we have no links (empty or not an array), return.
		if ( ! \is_array( $links ) || empty( $links ) ) {
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
			echo \esc_html__( 'No links found', 'wpcomsp_wayback_link_fixer' );
			return;
		}

		print \wp_kses(
			sprintf(
			// translators: %1$s is the number of broken links, %2$s is the total number of links.
				__( '<strong>%1$s</strong> broken out of <strong>%2$s</strong>', 'wpcomsp_wayback_link_fixer' ),
				absint( $stats['broken'] ),
				absint( $stats['total'] )
			),
			array(
				'strong' => array(),
			)
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

		$total  = \count( $links );
		$broken = \count(
			array_filter(
				$links,
				function ( Link $link ): bool {
					return $link->is_broken();
				}
			)
		);

		return compact( 'total', 'broken' );
	}
}
