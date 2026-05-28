<?php

/**
 * Tests for Check Snapshot Status Event
 *
 * @since 1.3.0
 *
 * @coversDefaultClass \Internet_Archive\Wayback_Machine_Link_Fixer\Event\Check_Snapshot_Status_Event
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Tests\Event;

use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link;
use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;
use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link_Repository;
use Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\Snapshot_Client;
use Internet_Archive\Wayback_Machine_Link_Fixer\Event\Check_Snapshot_Status_Event;

/**
 * Test_Check_Snapshot_Status_Event
 *
 * @group Event
 * @group Check_Snapshot_Status_Event
 */
class Test_Check_Snapshot_Status_Event extends \WP_UnitTestCase {

	private $wpdb;
	private $link_repository;

	/**
	 * Setup
	 */
	public function set_up(): void {
		$this->wpdb            = $GLOBALS['wpdb'];
		$this->link_repository = new Link_Repository();

		// Clear the actionscheduler_actions table.
		$this->wpdb->query( "TRUNCATE TABLE {$this->wpdb->prefix}actionscheduler_actions" );

		// Clear any iawmlf_snapshot_client filter.
		remove_all_filters( 'iawmlf_snapshot_client' );

		// Clear the link table.
		$this->wpdb->query( 'TRUNCATE TABLE ' . Settings::get_link_table_name() );

		parent::set_up();
	}

	/**
	 * Set the snapshot client to return a mocked response.
	 *
	 * @param array $response The response to return.
	 *
	 * @return void
	 */
	private function set_snapshot_client_response( array $response ): void {
		$service = $this->createMock( Snapshot_Client::class );
		$service->method( 'get_snapshot_status' )->willReturn( $response );

		add_filter( 'iawmlf_snapshot_client', fn() => $service );
	}

	/**
	 * @testdox When a status is checked, if the stus is error the message should be set in the message column.
	 *
	 * @return void
	 */
	public function test_error_status_sets_message(): void {
		$this->set_snapshot_client_response(
			array(
				'status'     => 'error',
				'message'    => 'Error message',
				'status_ext' => 'issue:no-access',

			)
		);

		$link = $this->link_repository->upsert( new Link( 'https://example.com' ) );

		$event = new Check_Snapshot_Status_Event();
		$event->setup();

		// Check the message is also in exception.

		try {
			$event( $link->get_id(), 'fake-id', 0 );
		} catch ( \Throwable $th ) {
			$this->assertEquals( "Error getting status for link id: {$link->get_id()}, error: Error message", $th->getMessage() );
		}

		// Get the link from the repository.
		$updated_link = $this->link_repository->find_by_id( $link->get_id() );

		// Check the message is set.
		$this->assertEquals( 'issue:no-access', $updated_link->get_message() );
		$this->assertEquals( Link::PROCESS_PENDING, $updated_link->get_archive_process() );
	}

	/**
	 * @testdox If the link has a the no access status, the link should be set as excluded. This should also mark the link as done.
	 *
	 * @return void
	 */
	public function test_no_access_status_sets_excluded(): void {
		$this->set_snapshot_client_response(
			array(
				'status'     => 'error',
				'status_ext' => 'error:no-access',
				'message'    => 'Error message',
			)
		);

		$link = $this->link_repository->upsert( new Link( 'https://example.com' ) );

		$event = new Check_Snapshot_Status_Event();
		$event->setup();

		// Check the message is also in exception.

		try {
			$event( $link->get_id(), 'fake-id', 0 );
		} catch ( \Throwable $th ) {
			$this->assertEquals( "Error getting status for link id: {$link->get_id()}, error: Error message", $th->getMessage() );
		}

		// Get the link from the repository.
		$updated_link = $this->link_repository->find_by_id( $link->get_id() );

		// Check the message is set.
		$this->assertTrue( $updated_link->is_excluded() );
		$this->assertEquals( Link::PROCESS_DONE, $updated_link->get_archive_process() );
	}

	/**
	 * @testdox If we have passed the max attempts, an exception should be thrown.
	 *
	 * @return void
	 */
	public function test_max_attempts_throws_exception(): void {
		$this->set_snapshot_client_response(
			array(
				'status'     => 'error',
				'status_ext' => 'error:no-access',
				'message'    => 'Error message',
			)
		);

		$link = $this->link_repository->upsert( new Link( 'https://example.com' ) );

		$event = new Check_Snapshot_Status_Event();
		$event->setup();

		try {
			$event( $link->get_id(), 'fake-id', 10 );
		} catch ( \Throwable $th ) {
			$this->assertEquals( "Max attempts reached for id:{$link->get_id()}", $th->getMessage() );
		}

		$link = $this->link_repository->find_by_id( $link->get_id() );
		// Check the link is marked as done.
		$this->assertEquals( Link::PROCESS_DONE, $link->get_archive_process() );
	}

	/**
	 * @testdox If the link is not found, an exception should be thrown.
	 *
	 * @return void
	 */
	public function test_link_not_found_throws_exception(): void {
		$this->set_snapshot_client_response(
			array(
				'status'     => 'error',
				'status_ext' => 'error:no-access',
				'message'    => 'Error message',
			)
		);

		$event = new Check_Snapshot_Status_Event();
		$event->setup();

		try {
			$event( 123, 'fake-id', 0 );
		} catch ( \Throwable $th ) {
			$this->assertEquals( 'Link not found for id:123', $th->getMessage() );
		}
	}

	/**
	 * @testdox If the status is pending, it should be added to the queue again with the attempt incremented. When this happens if the status was new, it should change to pending
	 *
	 * @return void
	 */
	public function test_pending_status_adds_to_queue(): void {
		$this->set_snapshot_client_response( array( 'status' => 'pending' ) );

		$link = $this->link_repository->upsert( new Link( 'https://example.com' ) );

		$event = new Check_Snapshot_Status_Event();
		$event->setup();

		$event( $link->get_id(), 'fake-id', 0 );

		// Get the link from the repository.
		$updated_link = $this->link_repository->find_by_id( $link->get_id() );

		// Check the link is not excluded.
		$this->assertFalse( $updated_link->is_excluded() );

		// Check the link is in the queue.
		$actions = $this->wpdb->get_results( "SELECT * FROM {$this->wpdb->prefix}actionscheduler_actions" );

		$this->assertCount( 1, $actions );

		$this->assertEquals( 'iawmlf_check_snapshot_status', $actions[0]->hook );
		$this->assertEquals(
			json_encode(
				array(
					'link_id' => $link->get_id(),
					'job_id'  => 'fake-id',
					'attempt' => 1,
				)
			),
			$actions[0]->args
		);

		// Status shoudl be pending.
		$this->assertEquals( Link::PROCESS_PENDING, $updated_link->get_archive_process() );
	}

	/**
	 * @testdox If the status is pending, it should be added to the queue again with the attempt incremented. When this happens if the status was done, it should not change to pending
	 *
	 * @return void
	 */
	public function test_pending_status_does_not_change_done_status(): void {
		$this->set_snapshot_client_response( array( 'status' => 'pending' ) );

		$link = $this->link_repository->upsert( new Link( 'https://example.com' ) );
		$link->set_archive_process( Link::PROCESS_DONE );
		$this->link_repository->upsert( $link );

		$event = new Check_Snapshot_Status_Event();
		$event->setup();

		$event( $link->get_id(), 'fake-id', 0 );

		// Get the link from the repository.
		$updated_link = $this->link_repository->find_by_id( $link->get_id() );

		// Status should still be done.
		$this->assertEquals( Link::PROCESS_DONE, $updated_link->get_archive_process() );
	}

	/**
	 * @testdox If the response comes back as success the link should be added to the Update Archive URL Event.
	 *
	 * @return void
	 */
	public function test_success_status_adds_to_update_archive_url_event(): void {
		$this->set_snapshot_client_response( array( 'status' => 'success' ) );

		$link = $this->link_repository->upsert( new Link( 'https://example.com' ) );

		$event = new Check_Snapshot_Status_Event();
		$event->setup();

		$event( $link->get_id(), 'fake-id', 0 );

		// Get the link from the repository.
		$updated_link = $this->link_repository->find_by_id( $link->get_id() );

		// Check the link is not excluded.
		$this->assertFalse( $updated_link->is_excluded() );

		// Link should be marked as pending.
		$this->assertEquals( Link::PROCESS_PENDING, $updated_link->get_archive_process() );

		// Check the link is in the queue.
		$actions = $this->wpdb->get_results( "SELECT * FROM {$this->wpdb->prefix}actionscheduler_actions" );

		$this->assertCount( 1, $actions );

		$this->assertEquals( 'iawmlf_update_archive_url', $actions[0]->hook );
		$this->assertEquals(
			json_encode(
				array(
					'link_id' => $link->get_id(),
					'attempt' => 0,
				)
			),
			$actions[0]->args
		);
	}

	/**
	 * @testdox If a system-excluded link (no-access) gets a success status, the exclusion is lifted and the stale error message cleared.
	 *
	 * @return void
	 */
	public function test_success_status_lifts_system_exclusion_and_clears_message(): void {
		$this->set_snapshot_client_response( array( 'status' => 'success' ) );

		$link = $this->link_repository->upsert( new Link( 'https://example.com' ) );
		$link->set_excluded();
		$link->set_message( 'error:no-access' );
		$this->link_repository->upsert( $link );

		$event = new Check_Snapshot_Status_Event();
		$event->setup();

		$event( $link->get_id(), 'fake-id', 0 );

		$updated_link = $this->link_repository->find_by_id( $link->get_id() );

		// System exclusion lifted on success.
		$this->assertFalse( $updated_link->is_excluded() );

		// Stale error message is cleared.
		$this->assertSame( '', $updated_link->get_message() );

		// Check the link is in the queue.
		$actions = $this->wpdb->get_results( "SELECT * FROM {$this->wpdb->prefix}actionscheduler_actions" );

		$this->assertCount( 1, $actions );

		$this->assertEquals( 'iawmlf_update_archive_url', $actions[0]->hook );
		$this->assertEquals(
			json_encode(
				array(
					'link_id' => $link->get_id(),
					'attempt' => 0,
				)
			),
			$actions[0]->args
		);
	}

	/**
	 * @testdox A manually excluded link keeps its exclusion and message even when the snapshot status comes back success.
	 *
	 * @return void
	 */
	public function test_success_status_preserves_manual_exclusion_and_message(): void {
		$manual_message = 'User Requested To Exclude (admin on 28 May 2026)';

		$this->set_snapshot_client_response( array( 'status' => 'success' ) );

		$link = $this->link_repository->upsert( new Link( 'https://example.com' ) );
		$link->set_excluded();
		$link->set_message( $manual_message );
		$this->link_repository->upsert( $link );

		$event = new Check_Snapshot_Status_Event();
		$event->setup();

		$event( $link->get_id(), 'fake-id', 0 );

		$updated_link = $this->link_repository->find_by_id( $link->get_id() );

		// Manual exclusion is sacred.
		$this->assertTrue( $updated_link->is_excluded(), 'Manual exclusion must survive a successful snapshot status.' );
		$this->assertSame( $manual_message, $updated_link->get_message(), 'Manual exclusion message must not be touched.' );

		// Still queues the archive URL update regardless of exclusion state.
		$actions = $this->wpdb->get_results( "SELECT * FROM {$this->wpdb->prefix}actionscheduler_actions" );

		$this->assertCount( 1, $actions );
		$this->assertEquals( 'iawmlf_update_archive_url', $actions[0]->hook );
	}
}
