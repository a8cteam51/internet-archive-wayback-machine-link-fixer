<?php

/**
 * Action scheduler event for adding a post on the site to the queue.
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Event;

use Exception;
use Throwable;
use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;
use Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\Wayback_Machine_Service;

defined( 'ABSPATH' ) || exit;

/**
 * Process Local Post Event class.
 */
class Process_Local_Post_Event {

	/**
	 * The event handle.
	 */
	public const HANDLE = 'iawmlf_process_local_post';

	/**
	 * The wayback machine service.
	 *
	 * @var Wayback_Machine_Service
	 */
	private $wayback_machine;

	/**
	 * Lazy setup of class
	 * This is run at call time, to reduce load on every page load.
	 *
	 * @return void
	 */
	public function setup(): void {
		$this->wayback_machine = new Wayback_Machine_Service();
	}

	/**
	 * Add to action scheduler.
	 *
	 * @param integer $post_id The post ID.
	 *
	 * @return void
	 */
	public static function add_to_queue( int $post_id ): void {
		self::add_to_queue_with_delay( $post_id, 0 );
	}

	/**
	 * Adds to the queue with a delay.
	 *
	 * @param integer $post_id The post ID.
	 * @param integer $delay   The delay in seconds.
	 *
	 * @return void
	 */
	public static function add_to_queue_with_delay( int $post_id, int $delay = 1 * \HOUR_IN_SECONDS ): void {
		// If there is already a scheduled event with this post ID, cancel it.
		as_unschedule_action( self::HANDLE, array( 'post_id' => $post_id ) );

		$time_to_run = time() + $delay;
		as_schedule_single_action(
			$time_to_run,
			self::HANDLE,
			array(
				'post_id' => $post_id,
			)
		);
	}

	/**
	 * Handle the event.
	 *
	 * @param integer $post_id The post ID.
	 *
	 * @return void
	 *
	 * @throws Exception If service is offline or post is not found.
	 * @throws Exception If the post is not found.
	 * @throws Exception If the permalink is not found.
	 * @throws Exception If the snapshot creation fails.
	 */
	public function __invoke( int $post_id ): void {

		$this->setup();
		// If Wayback Link Fixer is offline, add the event to the queue delayed.
		if ( ! $this->wayback_machine->is_online()['snapshot'] ) {
			self::add_to_queue_with_delay( $post_id );
			throw new Exception( esc_html( 'Service is offline, trying again in 1 hour.' ) );
		}

		// Get the post.
		$post = get_post( $post_id );

		// If the post is not found, throw an exception.
		if ( ! $post ) {
			throw new Exception( esc_html( "Post not found for id:{$post_id}" ) );
		}

		// Get the permalink.
		$permalink = get_permalink( $post );

		// If the permalink is not found, throw an exception.
		if ( ! $permalink ) {
			throw new Exception( esc_html( "Permalink not found for post id:{$post_id}" ) );
		}

		// Create a snapshot.
		try {
			$this->wayback_machine->create_snapshot( $permalink );
		} catch ( Throwable $th ) {
			throw new Exception( esc_html( "Failed to create snapshot for post id #{$post_id} : {$th->getMessage()}" ) );
		}

		// Add the last updated meta key.
		update_post_meta( $post_id, Settings::OWN_LINK_LAST_PROCESSED, time() );
	}
}
