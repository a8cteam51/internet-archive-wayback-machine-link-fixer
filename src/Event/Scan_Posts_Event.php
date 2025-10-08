<?php

/**
 * Action scheduler event for scanning posts for links.
 *
 * This will ensure that any old or imported posts are scanned for links.
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Event;

use WP_Query;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;
use WPCOMSpecialProjects\Wayback_Link_Fixer\WP_Post\WP_Post_Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Scan Posts Event class.
 */
class Scan_Posts_Event {

	/**
	 * The event handle.
	 */
	public const HANDLE = 'wlf_scan_existing_posts';

	/**
	 * Number of posts to process per call.
	 *
	 * @var integer
	 */
	private $posts_per_call = 10;

	/**
	 * Allowed post types.
	 *
	 * @var array
	 */
	private $allowed_post_types = array();

	/**
	 * The post handler.
	 *
	 * @var WP_Post_Controller
	 */
	private $post_controller;


	/**
	 * Lazy setup of class
	 * This is run at call time, to reduce load on every page load.
	 *
	 * @return void
	 */
	public function setup(): void {
		$this->posts_per_call     = Settings::get_posts_per_batch();
		$this->allowed_post_types = Settings::get_allowed_post_types();
		$this->post_controller    = new WP_Post_Controller();
	}

	/**
	 * Add to action scheduler.
	 *
	 * @return void
	 */
	public static function add_to_action_scheduler(): void {
		// Check if enabled in settings.
		$allow = Settings::should_scan_existing_posts();

		if ( ! $allow ) {
			return;
		}

		// Bail if action scheduler is not available.
		if ( ! function_exists( 'as_has_scheduled_action' ) ) {
			return;
		}

		// If the event is not scheduled, schedule it.
		if ( as_has_scheduled_action( self::HANDLE ) ) {
			return;
		}

		// Get the delay of the event.
		$interval = absint( apply_filters( 'iawmlf_scan_posts_interval', 10 * \MINUTE_IN_SECONDS ) );

		// If we have 0 interval, add as async action.
		if ( 0 === $interval ) {
			as_enqueue_async_action( self::HANDLE, array(), 'wayback-link-fixer' );
		} else {
			as_schedule_single_action( time() + $interval, self::HANDLE, array(), 'wayback-link-fixer' );
		}
	}

	/**
	 * The invocation of the event.
	 *
	 * @return void
	 */
	public function __invoke(): void {
		// Setup the class.
		$this->setup();

		// If the service is offline, we can't check the link.
		if ( ! Settings::is_archive_api_online() ) {
			self::add_to_action_scheduler();
			return;
		}

		// Look for more posts, which do not have the meta data.
		$query = new WP_Query(
			array(
				'post_type'              => $this->allowed_post_types,
				'posts_per_page'         => $this->posts_per_call,
				'cache_results'          => false,
				'update_post_meta_cache' => false,
				'meta_query'             => array(
					'relation' => 'OR',
					array(
						'key'     => Settings::LINK_META_KEY,
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => Settings::LINK_META_KEY,
						'value'   => time(),
						'compare' => '=',
					),
				),
			)
		);

		// If we have posts, process them.
		if ( $query->have_posts() ) {
			// Iterate over the posts.
			foreach ( $query->posts as $post ) {
				$this->post_controller->process_links_in_content( $post->ID );
			}
		}

		// Add the event to the action scheduler again.
		self::add_to_action_scheduler();
	}
}
