<?php

/**
 * Action shceduler event for routinely adding own posts to the internet archive
 *
 * This will ensure that all of the users own posts are added to the internet archive.
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Event;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;
use WPCOMSpecialProjects\Wayback_Link_Fixer\WP_Post\WP_Post_Controller;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Event\Process_Local_Post_Event;


/**
 * Scan Own Posts Event class.
 */
class Scan_Own_Posts_Event {

	/**
	 * The event handle.
	 */
	public const HANDLE = 'wlf_add_own_posts';

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
		$this->post_controller = new WP_Post_Controller();
	}

	/**
	 * Add to action scheduler.
	 *
	 * @return void
	 */
	public static function add_to_action_scheduler(): void {

		// If dont allow own links to be added, bail.
		if ( ! Settings::add_own_links() ) {
			return;
		}

		// If we dont allow to routinely add own posts, bail.
		if ( ! Settings::own_link_routinely_update() ) {
			return;
		}

		// Bail if action scheduler is not available.
		if ( ! \function_exists( 'as_has_scheduled_action' ) ) {
			return;
		}

		// If the event is not scheduled, schedule it.
		if ( \as_has_scheduled_action( self::HANDLE ) ) {
			return;
		}

		// Get the delay of the event.
		$interval = absint( \apply_filters( 'wlf_scan_own_posts_event_interval', 15 * \MINUTE_IN_SECONDS ) );

		// If we have 0 interval, add as async action.
		if ( 0 === $interval ) {
			\as_enqueue_async_action( self::HANDLE, array(), 'wayback-link-fixer' );
		} else {
			\as_schedule_single_action( \time() + $interval, self::HANDLE, array(), 'wayback-link-fixer' );
		}
	}

	/**
	 * Invoke the event.
	 *
	 * @return void
	 */
	public function __invoke(): void {
		// Run setup.
		$this->setup();

		$allowed_delay      = Settings::own_link_routine_update_interval();
		$allowed_post_types = Settings::own_link_allowed_post_types();
		$posts_per_call     = absint( \apply_filters( 'wlf_scan_own_posts_per_call', 10 ) );

		$args = array(
			'posts_per_page' => $posts_per_call,
			'post_type'      => $allowed_post_types,
			'post_status'    => 'publish',

			// Either doesnt have the metakey or the last checked date is less than the allowed delay.
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => Settings::OWN_LINK_LAST_PROCESSED,
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => Settings::OWN_LINK_LAST_PROCESSED,
					'value'   => time() - $allowed_delay,
					'compare' => '<',
					'type'    => 'NUMERIC',
				),
			),
		);

		// Get all posts that are in the defined post types and have not been checked since
		$posts = new \WP_Query( $args );

		// If we have no posts, bail.
		if ( ! $posts->have_posts() ) {
			return;
		}

		// Loop through the posts and add them to the queue.
		foreach ( $posts->posts as $post ) {
			$this->post_controller->add_own_post_to_wayback_machine( $post->ID );
		}
	}
}
