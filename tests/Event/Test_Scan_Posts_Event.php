<?php

/**
 * Tests for the Scan_Posts_Event class.
 *
 * @since 1.2.0
 *
 * @coversDefaultClass \Internet_Archive\Wayback_Machine_Link_Fixer\Event\Scan_Posts_Event
 */
declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Tests\Processor;

use Internet_Archive\Wayback_Machine_Link_Fixer\Event\Scan_Posts_Event;
use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;
use Internet_Archive\Wayback_Machine_Link_Fixer_Tests\Tools\Wayback_Machine_Helper;

/**
 * Test_Scan_Posts_Event
 */
class Test_Scan_Posts_Event extends \WP_UnitTestCase {

	/**
	 * On set_up, clear all used filters and hooks
	 *
	 * @return void
	 */
	public function set_up(): void {
		// Delete all rows from actionscheduler_actions.
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}actionscheduler_actions" );

		// Clear excluded posts option.
		\delete_option( Settings::LINK_FIXER_EXCLUDED_POSTS );

		// Clear relevant filters.
		\remove_all_filters( 'iawmlf_link_fixer_excluded_posts' );

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
	 * @testdox It should be possible to force the event to be added as async with 0 priority, replacing any existing scheduled actions.
	 *
	 * @return void
	 */
	public function test_force_add_to_action_scheduler(): void {
		update_option( Settings::PROCESS_LINKS, true );
		update_option( Settings::SCAN_EXISTING_POSTS, true );

		// Add the event normally.
		Scan_Posts_Event::add_to_action_scheduler();

		// Get the time of the scheduled action.
		$actions = $GLOBALS['wpdb']->get_results( "SELECT * FROM {$GLOBALS['wpdb']->prefix}actionscheduler_actions WHERE hook='iawmlf_scan_existing_posts' AND status='pending'" );
		$time_1 = $actions[0]->scheduled_date_gmt;

		// Now, force add the event again.
		Scan_Posts_Event::force_add_to_action_scheduler();

		// Get the time of the scheduled action again.
		$actions = $GLOBALS['wpdb']->get_results( "SELECT * FROM {$GLOBALS['wpdb']->prefix}actionscheduler_actions WHERE hook='iawmlf_scan_existing_posts' AND status='pending'" );
		$time_2 = $actions[0]->scheduled_date_gmt;
		$priority_2 = $actions[0]->priority;

		// Assert that the new time is before the old time, and that the priority is 0.
		$this->assertTrue( $time_2 < $time_1, 'The scheduled time was not updated to an earlier time.' );
		$this->assertEquals( 0, $priority_2, 'The priority was not set to 0.' );
	}

	/**
	 * @testdox Posts added to the excluded posts list should not be scanned by the event.
	 *
	 * @return void
	 */
	public function test_excluded_posts_are_not_scanned(): void {
		// Skip if the API is offline.
		if ( $GLOBALS['iawmlf_skip_live_api_tests'] === true ) {
			$this->markTestSkipped( 'Skipping live API tests' );
		}

		// Disable link processing so save_post hook doesn't process links during post creation.
		\update_option( Settings::PROCESS_LINKS, false );

		// Create 2 posts.
		$included_post_id = self::factory()->post->create( array( 'post_type' => 'post' ) );
		$excluded_post_id = self::factory()->post->create( array( 'post_type' => 'post' ) );

		// Clear any link meta that may have been set.
		\delete_post_meta( $included_post_id, Settings::LINK_META_KEY );
		\delete_post_meta( $excluded_post_id, Settings::LINK_META_KEY );

		// Now enable settings for the event.
		\update_option( Settings::PROCESS_LINKS, true );
		\update_option( Settings::SCAN_EXISTING_POSTS, true );

		// Add the excluded post to the exclusion list.
		\update_option( Settings::LINK_FIXER_EXCLUDED_POSTS, array( $excluded_post_id ) );

		// Run the event.
		$event = new Scan_Posts_Event();
		$event();

		// The included post should have been processed (has LINK_META_KEY meta).
		$this->assertNotFalse(
			\get_post_meta( $included_post_id, Settings::LINK_META_KEY, true ),
			'The included post should have been processed and have link meta.'
		);

		// The excluded post should NOT have been processed (no LINK_META_KEY meta).
		$this->assertFalse(
			\metadata_exists( 'post', $excluded_post_id, Settings::LINK_META_KEY ),
			'The excluded post should not have been processed.'
		);
	}
}
