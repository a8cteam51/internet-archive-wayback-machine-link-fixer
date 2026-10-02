<?php

/**
 * Tests for the Failed_Event_Garbage_Collection_Event class.
 *
 * @since 1.5.0
 *
 * @coversDefaultClass \Internet_Archive\Wayback_Machine_Link_Fixer\Event\Failed_Event_Garbage_Collection_Event
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Tests\Event;

use Internet_Archive\Wayback_Machine_Link_Fixer\Event\Failed_Event_Garbage_Collection_Event;

/**
 * Test_Failed_Event_Garbage_Collection_Event
 */
class Test_Failed_Event_Garbage_Collection_Event extends \WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();

		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}actionscheduler_actions" );
	}

	/**
	 * The IDs of the clean-up runs with a given status.
	 *
	 * @param string $status The Action Scheduler status.
	 *
	 * @return int[]
	 */
	private function action_ids( string $status ): array {
		global $wpdb;

		return array_map(
			'intval',
			$wpdb->get_col(
				$wpdb->prepare(
					"SELECT action_id FROM {$wpdb->prefix}actionscheduler_actions WHERE hook = %s AND status = %s",
					Failed_Event_Garbage_Collection_Event::HANDLE,
					$status
				)
			)
		);
	}

	/**
	 * @testdox A clean-up run queues the next one for tomorrow midnight, although it is in progress while it runs. (#386)
	 *
	 * @return void
	 */
	public function test_a_run_queues_the_next_one(): void {
		Failed_Event_Garbage_Collection_Event::add_to_action_scheduler();

		// Mark it as running, exactly as Action Scheduler does before it calls the event.
		$running_id = $this->action_ids( 'pending' )[0];
		\ActionScheduler::store()->log_execution( $running_id );

		( new Failed_Event_Garbage_Collection_Event() )();

		$pending = $this->action_ids( 'pending' );

		$this->assertCount( 1, $pending, 'The next run should be waiting.' );
		$this->assertNotSame( $running_id, $pending[0] );
		$this->assertSame(
			strtotime( 'tomorrow midnight' ),
			\ActionScheduler::store()->fetch_action( (string) $pending[0] )->get_schedule()->get_date()->getTimestamp()
		);
	}

	/**
	 * @testdox A clean-up run does not queue a second one when one is already waiting. (#386)
	 *
	 * @return void
	 */
	public function test_a_run_does_not_queue_a_second_one(): void {
		\as_schedule_single_action( strtotime( 'tomorrow midnight' ), Failed_Event_Garbage_Collection_Event::HANDLE );

		( new Failed_Event_Garbage_Collection_Event() )();

		$this->assertCount( 1, $this->action_ids( 'pending' ) );
	}
}
