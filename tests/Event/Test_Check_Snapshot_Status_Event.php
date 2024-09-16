<?php

/**
 * Tests for Check Snapshot Status Event
 *
 * @since 1.3.0
 *
 * @coversDefaultClass \WPCOMSpecialProjects\Wayback_Link_Fixer\Event\Check_Snapshot_Status_Event
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Tests\Event;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link_Repository;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Snapshot_Client;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Event\Check_Snapshot_Status_Event;

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

		// Clear any wlf_snapshot_client filter.
		remove_all_filters( 'wlf_snapshot_client' );

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

		add_filter( 'wlf_snapshot_client', fn() => $service );
	}

	/**
	 * @testdox When a status is checked, if the stus is error the message should be set in the message column.
	 *
	 * @return void
	 */
	public function test_error_status_sets_message(): void {
		$this->set_snapshot_client_response(
			array(
				'status'  => 'error',
				'message' => 'Error message',
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
		$this->assertEquals( 'Error message', $updated_link->get_message() );
	}

	/**
	 * @testdox If the link has a the no access status, the link should be set as excluded.
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
	 * @testdox If the status is pending, it should be added to the queue again with the attempt incremented.
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

		$this->assertEquals( 'wlf_check_snapshot_status', $actions[0]->hook );
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

		// Check the link is in the queue.
		$actions = $this->wpdb->get_results( "SELECT * FROM {$this->wpdb->prefix}actionscheduler_actions" );

		$this->assertCount( 1, $actions );

		$this->assertEquals( 'wlf_update_archive_url', $actions[0]->hook );
		$this->assertEquals( json_encode( array( 'link_id' => $link->get_id(), 'attempt' => 0 ) ), $actions[0]->args );
	}
}
