<?php

/**
 * Tests for the Scan_Own_Posts_Event class.
 *
 * @since 1.2.0
 */
declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Tests\Processor;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Event\Scan_Own_Posts_Event;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;
use WPCOMSpecialProjects\Wayback_Link_Fixer_Tests\Tools\Wayback_Machine_Helper;

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
		\remove_all_filters( 'wlf_add_own_content_to_wayback_machine' );
		\remove_all_filters( 'wlf_own_content_post_types' );
		\remove_all_filters( 'wlf_own_content_allow_post' );
		\remove_all_filters( 'wlf_routinely_update_wayback_machine' );
		\remove_all_filters( 'wlf_routinely_update_wayback_machine_interval' );

		// Clear the clients.
		$this->clear_clients();

				// Get all existing posts.
		$all = \get_posts( array( 'post_type' => 'any', 'posts_per_page' => -1 ) );
		// Iterate through all posts and remove them.
		foreach ( $all as $post ) {
			\wp_delete_post( $post->ID, true );
		}

		parent::set_up();
	}

	/**
	 * @testdox When init is run, the event to check own should be added if we allow scanning own posts and scanning at defined intervals.
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @return void
	 */
	public function test_add_own_posts_to_action_scheduler(): void {
		// Allow scanning own posts.
		\add_filter( 'wlf_own_content_allow_post', '__return_true' );
		// Allow scanning at defined intervals.
		\add_filter( 'wlf_routinely_update_wayback_machine', '__return_true' );

		// Run init.
		\do_action( 'init' );

		// Check that the event has been added to the action scheduler.
		$events = $GLOBALS['wpdb']->get_results( "SELECT * FROM {$GLOBALS['wpdb']->prefix}actionscheduler_actions WHERE hook='wlf_scan_existing_posts'" );
		$this->assertCount( 1, $events );
		$this->assertSame( 'pending', $events[0]->status );
	}

	/**
	 * @testdox If dont allow the adding of own events, the event should not be added to the action scheduler.
	 *
	 * @return void
	 */
	public function test_dont_allow_own_posts_to_action_scheduler(): void {
		// Dont allow scanning own posts.
		\add_filter( 'wlf_own_content_allow_post', '__return_false' );
		// Allow scanning at defined intervals.
		\add_filter( 'wlf_routinely_update_wayback_machine', '__return_true' );

		// Mock the WP_Post_Controller.
		Scan_Own_Posts_Event::add_to_action_scheduler();

		// Check that the event has not been added to the action scheduler.
		$events = $GLOBALS['wpdb']->get_results( "SELECT * FROM {$GLOBALS['wpdb']->prefix}actionscheduler_actions WHERE hook='wlf_scan_existing_posts'" );
		$this->assertCount( 0, $events );
	}

	/**
	 * @testdox If dont allow adding own posts via intervals, the event should not be added to the action scheduler.
	 *
	 * @return void
	 */
	public function test_dont_allow_own_posts_to_action_scheduler_via_intervals(): void {
		// Allow scanning own posts.
		\add_filter( 'wlf_own_content_allow_post', '__return_true' );
		// Dont allow scanning at defined intervals.
		\add_filter( 'wlf_routinely_update_wayback_machine', '__return_false' );

		// Mock the WP_Post_Controller.
		Scan_Own_Posts_Event::add_to_action_scheduler();

		// Check that the event has not been added to the action scheduler.
		$events = $GLOBALS['wpdb']->get_results( "SELECT * FROM {$GLOBALS['wpdb']->prefix}actionscheduler_actions WHERE hook='wlf_scan_existing_posts'" );
		$this->assertCount( 0, $events );
	}

	/**
	 * @testdox It should be possible to set the post types that are allowed to be scanned.
	 *
	 * @return void
	 */
	public function test_set_allowed_post_types(): void {


		// Allow with the filters.
		\add_filter( 'wlf_own_content_allow_post', '__return_true' );
		\add_filter( 'wlf_routinely_update_wayback_machine', '__return_true' );

		// Only allow post type 'post'.
		\add_filter(
			'wlf_own_content_post_types',
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
		$actions = $GLOBALS['wpdb']->get_results( "SELECT * FROM {$GLOBALS['wpdb']->prefix}actionscheduler_actions" );

		// Should be 1 added action.
		$this->assertCount( 1, $actions );

		// Check that the action is for the post.
		$this->assertSame($post_id, json_decode($actions[0]->args)->post_id);
	}

	/**
	 * @testdox When a post is process, we should only get posts that have not been checked in the last 24 hours.
	 *
	 * @return void
	 */
	public function test_only_get_posts_that_have_not_been_checked_in_last_24_hours(): void {
		\add_filter( 'wlf_own_content_allow_post', '__return_true' );
		\add_filter( 'wlf_routinely_update_wayback_machine', '__return_true' );

		// Set the interval to 24 hours.
		\add_filter( 'wlf_routinely_update_wayback_machine_interval', fn() => 24 * HOUR_IN_SECONDS );


		// Create 2 posts.
		$post_id_1 = $this->factory->post->create( array( 'post_type' => 'post' ) );
		$post_id_2 = $this->factory->post->create( array( 'post_type' => 'post' ) );

		// Set the meta, post 1 should be last checked 2 days ago and post 2 should be 1hour ago.
		$time_1 = time() - 2 * DAY_IN_SECONDS;
		$time_2 = time() - HOUR_IN_SECONDS;

		update_post_meta($post_id_1, Settings::OWN_LINK_LAST_PROCESSED, $time_1);
		update_post_meta($post_id_2, Settings::OWN_LINK_LAST_PROCESSED, $time_2);

		// Run the event.
		$event = new Scan_Own_Posts_Event();
		$event();

		// Get all action shceduler actions.
		$actions = $GLOBALS['wpdb']->get_results( "SELECT * FROM {$GLOBALS['wpdb']->prefix}actionscheduler_actions" );

		// Should be 1 added action.
		$this->assertCount( 1, $actions );

		// Check that the action is for the post 1.
		$this->assertSame($post_id_1, json_decode($actions[0]->args)->post_id);
	}
}
