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
}
