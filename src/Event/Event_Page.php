<?php

/**
 *The main page used to initiate and monitor events
 *
 * @since      1.0.0
 * @version    1.0.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Event;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Event\Events;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Viewer\Report_Viewer_Page;

defined( 'ABSPATH' ) || exit;

/**
 * Event Page
 */
class Event_Page {

	public const PAGE_SLUG                         = 'wpcomsp-wayback-link-events';
	public const CREATE_EVENT_AJAX_ACTION          = 'wpcomsp_wayback_link_fixer_create_event';
	public const CREATE_EVENT_AJAX_NONCE           = 'wpcomsp_wayback_link_fixer_create_event_nonce';
	public const SELECT2_EXCLUDE_POSTS_AJAX_ACTION = 'wpcomsp_wayback_link_fixer_select2_exclude_posts';
	public const SELECT2_EXCLUDE_POSTS_AJAX_NONCE  = 'wpcomsp_wayback_link_fixer_select2_exclude_posts_nonce';

	/**
	 * Access to the events repository.
	 *
	 * @var Events
	 */
	private Events $events;

	/**
	 * The pages menu hook.
	 *
	 * @since   1.0.0
	 * @var string|false
	 */
	private $menu_hook = false;

	/**
	 * Creates a new instance of the Events page.
	 *
	 * @since  1.0.0
	 */
	public function __construct() {
		$this->events = new Events();
	}

	/**
	 * Initialise the settings page.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function initialize(): void {
		add_action( 'admin_menu', array( $this, 'register_page' ), 10, 0 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_' . self::CREATE_EVENT_AJAX_ACTION, array( $this, 'create_event_ajax_handler' ) );
		add_action( 'wp_ajax_' . self::SELECT2_EXCLUDE_POSTS_AJAX_ACTION, array( $this, 'get_posts_by_post_type_ajax_handler' ) );

				// Enable network support for pages.
		if ( \is_multisite() ) {
			add_action( 'network_admin_menu', array( $this, 'register_page' ) );
		}
	}

	/**
	 * Register the settings page.
	 *
	 * @since   1.0.0
	 *
	 * @return  void
	 */
	public function register_page(): void {
		#
		// Register sub page
		$this->menu_hook = add_submenu_page(
			Report_Viewer_Page::PAGE_SLUG,
			__( 'New Report', 'wpcomsp_wayback_link_fixer' ),
			__( 'New Report', 'wpcomsp_wayback_link_fixer' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @since   1.0.0
	 *
	 * @param   string $page_hook The page hook.
	 *
	 * @return  void
	 */
	public function enqueue_scripts( string $page_hook ): void {
		if ( $this->menu_hook !== $page_hook ) {
			return;
		}

		// Include select2
		wp_enqueue_style( 'select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0-rc.0' );
		wp_enqueue_script( 'select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array( self::PAGE_SLUG ), '4.1.0-rc.0', true );

		// Register the admin-event script.
		wp_register_script(
			self::PAGE_SLUG,
			WPCOMSP_WAYBACK_LINK_FIXER_URL . 'assets/js/build/admin_event.js',
			array( 'jquery' ),
			WPCOMSP_WAYBACK_LINK_FIXER_METADATA['Version'],
			true
		);

		\wp_localize_script(
			self::PAGE_SLUG,
			'adminEvents',
			array(
				'ajaxUrl'                => \admin_url( 'admin-ajax.php' ),
				'userId'                 => \get_current_user_id(),
				'blogId'                 => \get_current_blog_id(),
				'nonceCreateEvent'       => \wp_create_nonce( self::CREATE_EVENT_AJAX_NONCE ),
				'ajaxActionCreateEvent'  => self::CREATE_EVENT_AJAX_ACTION,
				'nonceExcludePosts'      => \wp_create_nonce( self::SELECT2_EXCLUDE_POSTS_AJAX_NONCE ),
				'ajaxActionExcludePosts' => self::SELECT2_EXCLUDE_POSTS_AJAX_ACTION,
			)
		);
		\wp_enqueue_script( self::PAGE_SLUG );

		//  Register the styles.
		wp_enqueue_style(
			self::PAGE_SLUG,
			WPCOMSP_WAYBACK_LINK_FIXER_URL . 'assets/css/build/style-style.scss.css',
			array(),
			WPCOMSP_WAYBACK_LINK_FIXER_METADATA['Version']
		);
	}

	/**
	 * Render the settings page.
	 *
	 * @since   1.0.0
	 *
	 * @return  void
	 */
	public function render_page(): void {
		wpcomsp_wayback_link_fixer_render_template(
			'admin/event/event-page.php',
			array(
				'events' => $this->events->get_active_events(),
			)
		);
	}

	/**
	 * The ajax hanlder for creating a new event.
	 *
	 * @since   1.0.0
	 *
	 * @return void (never returns as JSON)
	 */
	public function create_event_ajax_handler(): void {

		// Verify the nonce.
		if ( ! array_key_exists( 'nonce', $_POST ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), self::CREATE_EVENT_AJAX_NONCE ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid nonce.', 'wpcomsp_wayback_link_fixer' ),
				)
			);
		}
		// Get the args from the request.
		$http_codes = array_key_exists( 'event_http', $_POST )
			? array_filter(
				array_map(
					'sanitize_text_field',
					$_POST['event_http']
				)
			)
			: array();

		$post_types = array_key_exists( 'event_post_types', $_POST )
			? array_map( 'sanitize_text_field', (array) $_POST['event_post_types'] )
			: array();

		$ignore_link_cache = array_key_exists( 'event_ignore_cache', $_POST ) && 'true' === $_POST['event_ignore_cache'];

		$exclude_posts = array_key_exists( 'event_exclude_posts', $_POST )
			? array_map( 'absint', (array) $_POST['event_exclude_posts'] )
			: array();

		$user_id = array_key_exists( 'user', $_POST ) ? (int) $_POST['user'] : 0;

		$fix_links = array_key_exists( 'event_fix_links', $_POST ) && 'true' === $_POST['event_fix_links'];

		// Get the array of blogs
		$blogs = array_key_exists( 'blog', $_POST )
			? array_map( 'absint', (array) $_POST['blog'] )
			: array( \get_current_blog_id() );

		// Create the event.
		try {
			// If we have no post types, throw an error.
			if ( empty( $post_types ) ) {
				throw new \Exception( __( 'You must select at least one post type.', 'wpcomsp_wayback_link_fixer' ) );
			}

			// If we have no http codes, throw an error.
			if ( empty( $http_codes ) ) {
				throw new \Exception( __( 'You must select at least one HTTP code.', 'wpcomsp_wayback_link_fixer' ) );
			}

			// Iterate over all the blogs
			$events = array();

			foreach ( $blogs as $blog_id ) {
				// Create the event.
				$event_id             = $this->events->create_event( $post_types, $http_codes, $exclude_posts, $ignore_link_cache, $user_id, $blog_id, $fix_links );
				$events[ $blog_id ][] = $event_id;
			}
		} catch ( \Throwable $th ) {
			wp_send_json_error(
				array(
					'message' => sprintf(
						// translators: %s is the error message.
						__( 'Failed to create event, error: %s', 'wpcomsp_wayback_link_fixer' ),
						$th->getMessage()
					),
					'data'    => array(
						'httpCodes'    => $http_codes,
						'postTypes'    => $post_types,
						'ignoreCache'  => $ignore_link_cache,
						'excludePosts' => $exclude_posts,
					),
				)
			);
		}

		// Cache the current blog id.
		$current_blog = get_current_blog_id();

		// Generate the HTML for the result.
		$html = '';
		foreach ( $events as $blog => $event_ids ) {

			// Swtich to the blog.
			switch_to_blog( $blog );

			foreach ( $event_ids as $event_id ) {
				$html .= ( function ( int $event_id ): string {
					// Get the event.
					$event = $this->events->get_event( $event_id );

					// If we have no event, return empty.
					if ( null === $event ) {
						return '';
					}
					return wpcomsp_wayback_link_fixer_render_template(
						'admin/event/event-row.php',
						array(
							'event' => $event,
							'id'    => $event_id,
						),
						false
					);
				} )( (int) $event_id );
			}
		}

		// Switch back to the original blog.
		switch_to_blog( $current_blog );

		// If the html is empty, throw an error.
		if ( '' === $html ) {
			$html = '<td><tr colspan="5">No event found</tr></td>';
		}

		// Return the event hash.
		wp_send_json_success(
			array(
				'message' => __( 'Event(s) created successfully.', 'wpcomsp_wayback_link_fixer' ),
				'data'    => array(
					'eventId'      => $event_id,
					'row'          => $html,
					'httpCodes'    => $http_codes,
					'postTypes'    => $post_types,
					'ignoreCache'  => $ignore_link_cache,
					'excludePosts' => $exclude_posts,
					'blogId'       => $blog_id,
					'userId'       => $user_id,
					'events'       => $events,
				),
			)
		);
	}

	/**
	 * Get all posts based on teh post type ajax handler.
	 *
	 * @since   1.0.0
	 *
	 * @return void (never returns as JSON)
	 */
	public function get_posts_by_post_type_ajax_handler(): void {
		// Get the nonce.
		if ( ! array_key_exists( 'nonce', $_POST ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), self::SELECT2_EXCLUDE_POSTS_AJAX_NONCE ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid nonce.', 'wpcomsp_wayback_link_fixer' ),
				)
			);
		}

		// Get the post types from the request.
		$post_types = array_key_exists( 'post_types', $_POST )
			? array_map( 'sanitize_text_field', (array) $_POST['post_types'] )
			: array();

		// If we have no post types, throw an error.
		if ( empty( $post_types ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You must select at least one post type.', 'wpcomsp_wayback_link_fixer' ),
				)
			);
		}

		// Get the search term.
		$search_term = array_key_exists( 'q', $_POST ) ? sanitize_text_field( (string) $_POST['q'] ) : '';

		// Get all posts for the post types.
		$posts = get_posts(
			array(
				's'              => $search_term,
				'post_type'      => $post_types,
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		// Get the title for a post based on its id.
		$wlf_get_title = function ( int $id ): string {
			$title     = get_the_title( $id );
			$post_type = get_post_type( $id );

			$title = '' === $title
				? __( 'No title', 'wpcomsp_wayback_link_fixer' )
				: $title;

			return sprintf(
				// Translators: %1$d is the post id, %2$s is the post title, %3$s is the post type.
				'#%d %s (%s)',
				$id,
				$title,
				$post_type
			);
		};

		// Return the posts.

		wp_send_json_success(
			array(
				'items' => array_map(
					fn( int $id ): array => array(
						'id'   => $id,
						'text' => $wlf_get_title( $id ),
					),
					$posts
				),
			)
		);
	}
}
