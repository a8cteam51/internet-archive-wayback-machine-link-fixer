<?php

/**
 * Tests for the Scan_Own_Posts_Event class.
 *
 * @since 1.2.0
 *
 * @coversDefaultClass \Internet_Archive\Wayback_Machine_Link_Fixer\Event\Scan_Own_Posts_Event
 */
declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Tests\Processor;

use Internet_Archive\Wayback_Machine_Link_Fixer\Event\Scan_Own_Posts_Event;
use Internet_Archive\Wayback_Machine_Link_Fixer\Event\Process_Local_Post_Event;
use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;
use Internet_Archive\Wayback_Machine_Link_Fixer_Tests\Tools\Wayback_Machine_Helper;

/**
 * Test_Scan_Own_Posts_Event
 */
class Test_Scan_Own_Posts_Event extends \WP_UnitTestCase {

	use Wayback_Machine_Helper;

	/**
	 * On set_up, clear all used filters and hooks
	 *
	 * @return void
	 */
	public function set_up(): void {
		// Delete all rows from actionscheduler_actions.
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}actionscheduler_actions" );

		// Clear all filters.
		\remove_all_filters( 'iawmlf_add_own_content_to_wayback_machine' );
		\remove_all_filters( 'iawmlf_own_content_post_types' );
		\remove_all_filters( 'iawmlf_own_content_allow_post' );
		\remove_all_filters( 'iawmlf_routinely_update_wayback_machine' );
		\remove_all_filters( 'iawmlf_routinely_update_wayback_machine_interval' );
		\remove_all_filters( 'iawmlf_auto_archiver_excluded_posts' );

		// Clear the clients.
		$this->clear_clients();

				// Get all existing posts.
		$all = \get_posts(
			array(
				'post_type'      => 'any',
				'posts_per_page' => -1,
			)
		);
		// Iterate through all posts and remove them.
		foreach ( $all as $post ) {
			\wp_delete_post( $post->ID, true );
		}

		parent::set_up();
	}

	/**
	 * @testdox If dont allow the adding of own events, the event should not be added to the action scheduler.
	 *
	 * @return void
	 */
	public function test_dont_allow_own_posts_to_action_scheduler(): void {
		// Dont allow scanning own posts.
		\add_filter( 'iawmlf_own_content_allow_post', '__return_false' );
		// Allow scanning at defined intervals.
		\add_filter( 'iawmlf_routinely_update_wayback_machine', '__return_true' );

		// Mock the WP_Post_Controller.
		Scan_Own_Posts_Event::add_to_action_scheduler();

		// Check that the event has not been added to the action scheduler.
		$events = $GLOBALS['wpdb']->get_results( "SELECT * FROM {$GLOBALS['wpdb']->prefix}actionscheduler_actions WHERE hook='iawmlf_scan_existing_posts'" );
		$this->assertCount( 0, $events );
	}

	/**
	 * @testdox If dont allow adding own posts via intervals, the event should not be added to the action scheduler.
	 *
	 * @return void
	 */
	public function test_dont_allow_own_posts_to_action_scheduler_via_intervals(): void {
		// Allow scanning own posts.
		\add_filter( 'iawmlf_own_content_allow_post', '__return_true' );
		// Dont allow scanning at defined intervals.
		\add_filter( 'iawmlf_routinely_update_wayback_machine', '__return_false' );

		// Mock the WP_Post_Controller.
		Scan_Own_Posts_Event::add_to_action_scheduler();

		// Check that the event has not been added to the action scheduler.
		$events = $GLOBALS['wpdb']->get_results( "SELECT * FROM {$GLOBALS['wpdb']->prefix}actionscheduler_actions WHERE hook='iawmlf_scan_existing_posts'" );
		$this->assertCount( 0, $events );
	}

	/**
	 * @testdox It should be possible to set the post types that are allowed to be scanned.
	 *
	 * @return void
	 */
	public function test_set_allowed_post_types(): void {

		// Allow with the filters.
		\add_filter( 'iawmlf_own_content_allow_post', '__return_true' );
		\add_filter( 'iawmlf_routinely_update_wayback_machine', '__return_true' );
		\add_filter( 'iawmlf_scan_existing_posts', '__return_false' );

		// Only allow post type 'post'.
		\add_filter(
			'iawmlf_own_content_post_types',
			function () {
				return array( 'post' );
			}
		);

		// Create a post and page.
		$post_id = $this->factory->post->create( array( 'post_type' => 'post' ) );
		$page_id = $this->factory->post->create( array( 'post_type' => 'page' ) );

		// Run the event.
		$event = new Scan_Own_Posts_Event();
		$event();

		// Get all action shceduler actions.
		$actions = $GLOBALS['wpdb']->get_results( "SELECT * FROM {$GLOBALS['wpdb']->prefix}actionscheduler_actions WHERE status = 'pending'" );

		// Should be 1 added action.
		$this->assertCount( 1, $actions );

		// Check that the action is for the post.
		$this->assertSame( $post_id, json_decode( $actions[0]->args )->post_id );
	}

	/**
	 * @testdox When a post is process, we should only get posts that have not been checked in the last 24 hours.
	 *
	 * @return void
	 */
	public function test_only_get_posts_that_have_not_been_checked_in_last_24_hours(): void {
		\add_filter( 'iawmlf_routinely_update_wayback_machine', '__return_true' );
		\add_filter( 'iawmlf_own_content_allow_post', '__return_false' );
		$backup_settings = get_option( Settings::PROCESS_LINKS );
		update_option( Settings::PROCESS_LINKS, false );

		// Set the interval to 24 hours.
		\add_filter( 'iawmlf_routinely_update_wayback_machine_interval', fn() => 1 );

		// Create 2 posts.
		$post_id_1 = $this->factory->post->create( array( 'post_type' => 'post' ) );
		$post_id_2 = $this->factory->post->create( array( 'post_type' => 'post' ) );

		// Set the meta, post 1 should be last checked 2 days ago and post 2 should be 1hour ago.
		$time_1 = time() - 2 * DAY_IN_SECONDS;
		$time_2 = time() - 1 * HOUR_IN_SECONDS;
		update_post_meta( $post_id_1, Settings::OWN_LINK_LAST_PROCESSED, $time_1 );
		update_post_meta( $post_id_2, Settings::OWN_LINK_LAST_PROCESSED, $time_2 );

		// Reset the filters and allow adding own posts.
		\add_filter( 'iawmlf_own_content_allow_post', '__return_true' );
		update_option( Settings::PROCESS_LINKS, $backup_settings );

		// Run the event.
		$event = new Scan_Own_Posts_Event();
		$event();

		// Get all action shceduler actions.
		$actions = $GLOBALS['wpdb']->get_results( "SELECT * FROM {$GLOBALS['wpdb']->prefix}actionscheduler_actions WHERE status='pending'" );
		// Should be 1 added action.
		$this->assertCount( 1, $actions );

		// Check that the action is for the post 1.
		$this->assertSame( $post_id_1, json_decode( $actions[0]->args )->post_id );
	}


	/**
	 * @testdox Posts excluded via the auto archiver excluded posts setting should not be scanned.
	 *
	 * @return void
	 */
	public function test_excluded_posts_are_not_scanned(): void {
		// Allow scanning own posts.
		\add_filter( 'iawmlf_own_content_allow_post', '__return_true' );
		\add_filter( 'iawmlf_routinely_update_wayback_machine', '__return_true' );

		// Create 2 posts.
		$post_id_1 = $this->factory->post->create( array( 'post_type' => 'post' ) );
		$post_id_2 = $this->factory->post->create( array( 'post_type' => 'post' ) );

		// Exclude post 1 via the filter.
		\add_filter(
			'iawmlf_auto_archiver_excluded_posts',
			function () use ( $post_id_1 ) {
				return array( $post_id_1 );
			}
		);

		// Run the event.
		$event = new Scan_Own_Posts_Event();
		$event();

		// Get all pending action scheduler actions.
		$actions = $GLOBALS['wpdb']->get_results( "SELECT * FROM {$GLOBALS['wpdb']->prefix}actionscheduler_actions WHERE status='pending'" );

		// Should be 1 added action (only post 2).
		$this->assertCount( 1, $actions );

		// Check that the action is for post 2.
		$this->assertSame( $post_id_2, json_decode( $actions[0]->args )->post_id );
	}

	/**
	 * @testdox The scan should queue its posts with no delay, as they are already due to be archived.
	 *
	 * @return void
	 */
	public function test_scan_queues_posts_with_no_delay(): void {
		\add_filter( 'iawmlf_own_content_allow_post', '__return_true' );
		\add_filter( 'iawmlf_routinely_update_wayback_machine', '__return_true' );

		$post_id = $this->factory->post->create( array( 'post_type' => 'post' ) );

		$event = new Scan_Own_Posts_Event();
		$event();

		$pending = $this->get_pending_action( $post_id );

		$this->assertNotNull( $pending );

		// Due now, not an hour out.
		$this->assertLessThan( 60, \strtotime( $pending->scheduled_date_gmt ) - time() );
	}

	/**
	 * @testdox A post that already has a job waiting should not be picked up by the scan, as re-queuing would reset its delay.
	 *
	 * @return void
	 */
	public function test_posts_already_in_the_queue_are_not_rescanned(): void {
		\add_filter( 'iawmlf_own_content_allow_post', '__return_true' );
		\add_filter( 'iawmlf_routinely_update_wayback_machine', '__return_true' );

		$post_id = $this->factory->post->create( array( 'post_type' => 'post' ) );

		// The save hook queues the post an hour out.
		Process_Local_Post_Event::add_to_queue_with_delay( $post_id );

		$queued = $this->get_pending_action( $post_id );

		$this->assertNotNull( $queued );

		$event = new Scan_Own_Posts_Event();

		// Four scan passes, with 15 minutes of simulated time between each.
		for ( $pass = 0; $pass < 4; $pass++ ) {
			$this->rewind_pending_action( $post_id, 15 * MINUTE_IN_SECONDS );

			$event();

			$pending = $this->get_pending_action( $post_id );

			$this->assertNotNull( $pending, "No pending job after pass {$pass}." );

			// Same job as the one the save hook queued, untouched by the scan.
			$this->assertSame( (int) $queued->action_id, (int) $pending->action_id, "The job was replaced on pass {$pass}." );
		}

		// The job is now due, having counted down the full hour.
		$this->assertLessThan( 60, \strtotime( $pending->scheduled_date_gmt ) - time() );

		// Nothing was cancelled along the way.
		$this->assertSame( 0, $this->count_actions( $post_id, 'canceled' ) );
	}

	/**
	 * @testdox Posts that are already queued should not use up the scans batch, so every pass queues new work.
	 *
	 * @return void
	 */
	public function test_queued_posts_do_not_use_up_the_batch(): void {
		\add_filter( 'iawmlf_own_content_allow_post', '__return_true' );
		\add_filter( 'iawmlf_routinely_update_wayback_machine', '__return_true' );

		// A batch of 2, with 4 posts to get through.
		\add_filter( 'iawmlf_scan_own_posts_per_call', fn() => 2 );

		$post_ids = $this->factory->post->create_many( 4, array( 'post_type' => 'post' ) );

		$event = new Scan_Own_Posts_Event();
		$event();

		$this->assertSame( 2, $this->count_pending_actions() );

		// The next pass should queue the other two, not re-queue the first two.
		$event();

		$this->assertSame( 4, $this->count_pending_actions() );

		// One job per post, and none of them cancelled.
		foreach ( $post_ids as $post_id ) {
			$this->assertNotNull( $this->get_pending_action( $post_id ) );
			$this->assertSame( 0, $this->count_actions( $post_id, 'canceled' ) );
		}
	}

	/**
	 * Gets the single pending job for a post, or null.
	 *
	 * @param integer $post_id The post id.
	 *
	 * @return object|null
	 */
	private function get_pending_action( int $post_id ) {
		return $GLOBALS['wpdb']->get_row(
			$GLOBALS['wpdb']->prepare(
				"SELECT * FROM {$GLOBALS['wpdb']->prefix}actionscheduler_actions WHERE status = 'pending' AND hook = %s AND args = %s",
				Process_Local_Post_Event::HANDLE,
				\wp_json_encode( array( 'post_id' => $post_id ) )
			)
		);
	}

	/**
	 * Counts the jobs for a post with a given status.
	 *
	 * @param integer $post_id The post id.
	 * @param string  $status  The action scheduler status.
	 *
	 * @return integer
	 */
	private function count_actions( int $post_id, string $status ): int {
		return (int) $GLOBALS['wpdb']->get_var(
			$GLOBALS['wpdb']->prepare(
				"SELECT COUNT(*) FROM {$GLOBALS['wpdb']->prefix}actionscheduler_actions WHERE status = %s AND hook = %s AND args = %s",
				$status,
				Process_Local_Post_Event::HANDLE,
				\wp_json_encode( array( 'post_id' => $post_id ) )
			)
		);
	}

	/**
	 * Counts all pending process events.
	 *
	 * @return integer
	 */
	private function count_pending_actions(): int {
		return (int) $GLOBALS['wpdb']->get_var(
			$GLOBALS['wpdb']->prepare(
				"SELECT COUNT(*) FROM {$GLOBALS['wpdb']->prefix}actionscheduler_actions WHERE status = 'pending' AND hook = %s",
				Process_Local_Post_Event::HANDLE
			)
		);
	}

	/**
	 * Simulates time passing by moving the pending job's due date earlier.
	 *
	 * @param integer $post_id The post id.
	 * @param integer $seconds How much time has passed.
	 *
	 * @return void
	 */
	private function rewind_pending_action( int $post_id, int $seconds ): void {
		$action = $this->get_pending_action( $post_id );

		if ( null === $action ) {
			return;
		}

		$new_timestamp = \strtotime( $action->scheduled_date_gmt ) - $seconds;

		$GLOBALS['wpdb']->update(
			$GLOBALS['wpdb']->prefix . 'actionscheduler_actions',
			array(
				'scheduled_date_gmt'   => \gmdate( 'Y-m-d H:i:s', $new_timestamp ),
				'scheduled_date_local' => \gmdate( 'Y-m-d H:i:s', $new_timestamp ),
				'schedule'             => \preg_replace( '/i:\d{9,};/', 'i:' . $new_timestamp . ';', $action->schedule ),
			),
			array( 'action_id' => $action->action_id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);
	}

}
