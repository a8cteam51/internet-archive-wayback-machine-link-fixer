<?php

/**
 * Class which handles the AJAX requests for post search.
 *
 * Used by the post exclusion settings field to search for posts
 * by title, slug, or ID.
 *
 * @since 1.4.0
 */

declare( strict_types = 1 );

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Ajax;

use Exception;
use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Post_Search_Ajax
 */
class Post_Search_Ajax {

	/**
	 * The action name for the AJAX request.
	 */
	public const ACTION = 'iawmlf_post_search';

	/**
	 * The nonce name for the AJAX request.
	 */
	public const NONCE = 'iawmlf_post_search';

	/**
	 * The minimum number of characters required to search.
	 */
	private const MIN_CHARS = 2;

	/**
	 * Register the ajax action.
	 *
	 * @return void
	 */
	public static function register_ajax_call(): void {
		$handler = static function () {
			( new self() )->__invoke();
		};

		add_action( 'wp_ajax_' . self::ACTION, $handler );
	}

	/**
	 * The invocation method for the AJAX request.
	 *
	 * @return void
	 */
	public function __invoke(): void {
		// Validate the request.
		try {
			$this->validate_request();
		} catch ( Exception $e ) {
			$this->send_error( $e->getMessage(), 403 );
		}

		$search  = sanitize_text_field( wp_unslash( $_POST['search'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, Checked above.
		$context = isset( $_POST['context'] ) ? sanitize_text_field( wp_unslash( $_POST['context'] ) ) : 'link_fixer'; // phpcs:ignore WordPress.Security.NonceVerification.Missing, Checked above.

		// Get the post types and excluded posts based on the context.
		if ( 'auto_archiver' === $context ) {
			$post_types   = Settings::own_link_allowed_post_types();
			$excluded_ids = Settings::get_auto_archiver_excluded_posts();
		} else {
			$post_types   = Settings::get_allowed_post_types();
			$excluded_ids = Settings::get_link_fixer_excluded_posts();
		}

		// Get the results.
		$results = $this->search_posts( $search, $post_types, $excluded_ids );

		$this->send_success( $results );
	}

	/**
	 * Search for posts by title, slug, or ID.
	 *
	 * Uses WP_Query for proper caching and WordPress compatibility.
	 *
	 * @param string   $search       The search term.
	 * @param string[] $post_types   The post types to search within.
	 * @param int[]    $excluded_ids Post IDs to exclude from results.
	 *
	 * @return array
	 */
	private function search_posts( string $search, array $post_types, array $excluded_ids = array() ): array {
		$found_ids = array();
		$results   = array();

		// If numeric, try exact ID match first.
		if ( is_numeric( $search ) ) {
			$post_id = absint( $search );
			if ( $post_id > 0 ) {
				$id_query = new \WP_Query(
					array(
						'p'              => $post_id,
						'post_type'      => $post_types,
						'post_status'    => 'publish',
						'posts_per_page' => 1,
						'post__not_in'   => $excluded_ids,
					)
				);

				if ( $id_query->have_posts() ) {
					$post        = $id_query->posts[0];
					$found_ids[] = $post->ID;
					$results[]   = $this->format_result_from_post( $post );
				}
			}
		}

		// Search by title and slug using WP_Query with a scoped posts_where filter.
		$where_filter = $this->get_title_slug_where_filter( $search );
		add_filter( 'posts_where', $where_filter, 10, 2 );

		$search_query = new \WP_Query(
			array(
				'post_type'                => $post_types,
				'post_status'              => 'publish',
				'posts_per_page'           => -1,
				'orderby'                  => 'title',
				'order'                    => 'ASC',
				'suppress_filters'         => false,
				'iawmlf_title_slug_search' => true,
				'post__not_in'             => $excluded_ids,
			)
		);

		remove_filter( 'posts_where', $where_filter, 10 );

		foreach ( $search_query->posts as $post ) {
			if ( ! in_array( $post->ID, $found_ids, true ) ) {
				$found_ids[] = $post->ID;
				$results[]   = $this->format_result_from_post( $post );
			}
		}

		return $results;
	}

	/**
	 * Build a posts_where filter callback for title/slug LIKE search.
	 *
	 * @param string $search The search term.
	 *
	 * @return callable
	 */
	private function get_title_slug_where_filter( string $search ): callable {
		return static function ( string $where, \WP_Query $query ) use ( $search ): string {
			if ( ! $query->get( 'iawmlf_title_slug_search' ) ) {
				return $where;
			}

			global $wpdb;
			$like   = '%' . $wpdb->esc_like( $search ) . '%';
			$where .= $wpdb->prepare(
				" AND ({$wpdb->posts}.post_title LIKE %s OR {$wpdb->posts}.post_name LIKE %s)",
				$like,
				$like
			);
			return $where;
		};
	}

	/**
	 * Format a WP_Post object into a result array.
	 *
	 * @param \WP_Post $post The post object.
	 *
	 * @return array
	 */
	private function format_result_from_post( \WP_Post $post ): array {
		$post_type_object = get_post_type_object( $post->post_type );
		$post_type_label  = $post_type_object ? $post_type_object->labels->singular_name : $post->post_type;

		return array(
			'id'        => $post->ID,
			'title'     => $post->post_title,
			'slug'      => $post->post_name,
			'post_type' => $post_type_label,
		);
	}

	/**
	 * Validates the request.
	 *
	 * @return void
	 *
	 * @throws Exception If the request is invalid.
	 */
	private function validate_request(): void {
		// Check the user has permission.
		if ( ! current_user_can( 'manage_options' ) ) {
			throw new Exception( 'You do not have permission to perform this action.' );
		}

		// Check we have the search term in the request.
		if ( ! isset( $_POST['search'] ) ) {
			throw new Exception( 'Search term not set in request.' );
		}

		// Check the nonce is set in the request.
		if ( ! isset( $_POST['nonce'] ) ) {
			throw new Exception( 'Nonce not set in request.' );
		}

		// Verify the nonce.
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), self::ACTION ) ) {
			throw new Exception( 'Invalid nonce.' );
		}

		// Check the search term meets minimum length.
		$search = sanitize_text_field( wp_unslash( $_POST['search'] ) );
		if ( strlen( $search ) < self::MIN_CHARS ) {
			throw new Exception( 'Search term must be at least ' . self::MIN_CHARS . ' characters.' ); // phpcs:ignore
		}
	}

	/**
	 * Sends an error response.
	 *
	 * @param string  $message The error message.
	 * @param integer $status  The HTTP status code.
	 *
	 * @return void
	 */
	private function send_error( string $message, int $status = 500 ): void {
		wp_send_json_error( $message, $status );
	}

	/**
	 * Sends a success response.
	 *
	 * @param mixed $data The data to send.
	 *
	 * @return void
	 */
	private function send_success( $data ): void {
		wp_send_json_success( $data );
	}
}
