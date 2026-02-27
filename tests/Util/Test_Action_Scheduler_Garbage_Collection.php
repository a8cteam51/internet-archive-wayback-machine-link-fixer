<?php

/**
 * Tests for Action_Scheduler_Garbage_Collection
 *
 * @since 1.3.5
 *
 * @coversDefaultClass \Internet_Archive\Wayback_Machine_Link_Fixer\Util\Action_Scheduler_Garbage_Collection
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Tests\Util;

use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link;
use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link_Repository;
use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;
use Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\Snapshot_Client;
use Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\Link_Checker_Client;
use Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\Exception\Service_Offline_Exception;
use Internet_Archive\Wayback_Machine_Link_Fixer\Event\Check_Snapshot_Status_Event;
use Internet_Archive\Wayback_Machine_Link_Fixer\Event\Check_Validator_Status;
use Internet_Archive\Wayback_Machine_Link_Fixer\Event\Create_New_Snapshot_Event;
use Internet_Archive\Wayback_Machine_Link_Fixer\Event\Update_Archive_URL_Event;
use Internet_Archive\Wayback_Machine_Link_Fixer\Util\Action_Scheduler_Garbage_Collection;

/**
 * Test_Action_Scheduler_Garbage_Collection
 *
 * @group Util
 * @group Action_Scheduler_Garbage_Collection
 */
class Test_Action_Scheduler_Garbage_Collection extends \WP_UnitTestCase {

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

		// Clear any mock client filters.
		remove_all_filters( 'iawmlf_snapshot_client' );
		remove_all_filters( 'iawmlf_link_checker_client' );

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
	 * @testdox When cleaning up check snapshot status events, only the last attempt should remain and all earlier attempts should be deleted.
	 *
	 * @return void
	 */
	public function test_clean_check_snapshot_status_events_keeps_last_attempt(): void {
		$this->set_snapshot_client_response( array( 'status' => 'pending' ) );

		// Create 3 links.
		$link_a = $this->link_repository->upsert( new Link( 'https://example.com' ) );
		$link_b = $this->link_repository->upsert( new Link( 'https://example.org' ) );
		$link_c = $this->link_repository->upsert( new Link( 'https://example.net' ) );

		$event = new Check_Snapshot_Status_Event();
		$event->setup();

		// Link A with job-aaa: 3 invocations (0,1,2) → creates actions for attempts 1,2,3.
		$event( $link_a->get_id(), 'fake-job-aaa', 0 );
		$event( $link_a->get_id(), 'fake-job-aaa', 1 );
		$event( $link_a->get_id(), 'fake-job-aaa', 2 );

		// Link B with job-bbb: 2 invocations (0,1) → creates actions for attempts 1,2.
		$event( $link_b->get_id(), 'fake-job-bbb', 0 );
		$event( $link_b->get_id(), 'fake-job-bbb', 1 );

		// Link C with job-aaa (same job as A, different link): 1 invocation (0) → creates action for attempt 1.
		$event( $link_c->get_id(), 'fake-job-aaa', 0 );


		// Mark them all as failed to simulate Action Scheduler marking them after exception/timeout.
		$this->wpdb->update(
			"{$this->wpdb->prefix}actionscheduler_actions",
			array( 'status' => 'failed' ),
			array(
				'hook'   => Check_Snapshot_Status_Event::HANDLE,
				'status' => 'pending',
			)
		);

		// Run the garbage collector.
		$gc = new Action_Scheduler_Garbage_Collection();
		$gc->clean_check_snapshot_status_events();

		// Should have 3 actions remaining (one per job_id + link_id group).
		$remaining = $this->wpdb->get_results(
			"SELECT * FROM {$this->wpdb->prefix}actionscheduler_actions WHERE hook = 'iawmlf_check_snapshot_status'"
		);
		$this->assertCount( 3, $remaining );

		// Build a lookup of remaining args by link_id.
		$remaining_by_link = array();
		foreach ( $remaining as $action ) {
			$args = json_decode( $action->args, true );
			$remaining_by_link[ $args['link_id'] ] = $args;
		}

		// Link A group: should keep attempt 3.
		$this->assertEquals( 3, $remaining_by_link[ $link_a->get_id() ]['attempt'] );
		$this->assertEquals( 'fake-job-aaa', $remaining_by_link[ $link_a->get_id() ]['job_id'] );

		// Link B group: should keep attempt 2.
		$this->assertEquals( 2, $remaining_by_link[ $link_b->get_id() ]['attempt'] );
		$this->assertEquals( 'fake-job-bbb', $remaining_by_link[ $link_b->get_id() ]['job_id'] );

		// Link C group: should keep attempt 1 (only one in group, untouched).
		$this->assertEquals( 1, $remaining_by_link[ $link_c->get_id() ]['attempt'] );
		$this->assertEquals( 'fake-job-aaa', $remaining_by_link[ $link_c->get_id() ]['job_id'] );
	}

	/**
	 * @testdox When there are no failed actions, the garbage collector should do nothing without error.
	 *
	 * @return void
	 */
	public function test_clean_check_snapshot_status_events_empty_table_does_nothing(): void {
		$gc = new Action_Scheduler_Garbage_Collection();
		$gc->clean_check_snapshot_status_events();

		$remaining = $this->wpdb->get_results(
			"SELECT * FROM {$this->wpdb->prefix}actionscheduler_actions WHERE hook = 'iawmlf_check_snapshot_status'"
		);
		$this->assertCount( 0, $remaining );
	}

	/**
	 * @testdox When each group only has a single action, nothing should be deleted.
	 *
	 * @return void
	 */
	public function test_clean_check_snapshot_status_events_single_attempt_not_deleted(): void {
		$this->set_snapshot_client_response( array( 'status' => 'pending' ) );

		$link_a = $this->link_repository->upsert( new Link( 'https://example.com' ) );
		$link_b = $this->link_repository->upsert( new Link( 'https://example.org' ) );

		$event = new Check_Snapshot_Status_Event();
		$event->setup();

		// Each link gets a single invocation → 1 action each.
		$event( $link_a->get_id(), 'fake-job-aaa', 0 );
		$event( $link_b->get_id(), 'fake-job-bbb', 0 );

		// Mark as failed.
		$this->wpdb->update(
			"{$this->wpdb->prefix}actionscheduler_actions",
			array( 'status' => 'failed' ),
			array(
				'hook'   => Check_Snapshot_Status_Event::HANDLE,
				'status' => 'pending',
			)
		);

		// Run the garbage collector.
		$gc = new Action_Scheduler_Garbage_Collection();
		$gc->clean_check_snapshot_status_events();

		// Both actions should still exist.
		$remaining = $this->wpdb->get_results(
			"SELECT * FROM {$this->wpdb->prefix}actionscheduler_actions WHERE hook = 'iawmlf_check_snapshot_status'"
		);
		$this->assertCount( 2, $remaining );
	}

	/**
	 * @testdox When a before date is passed, only actions scheduled before that date should be cleaned up.
	 *
	 * @return void
	 */
	public function test_clean_check_snapshot_status_events_before_date_only_cleans_old(): void {
		$this->set_snapshot_client_response( array( 'status' => 'pending' ) );

		$link_old = $this->link_repository->upsert( new Link( 'https://old-example.com' ) );
		$link_new = $this->link_repository->upsert( new Link( 'https://new-example.com' ) );

		$event = new Check_Snapshot_Status_Event();
		$event->setup();

		// Old link: 2 invocations (0,1) → creates actions for attempts 1,2.
		$event( $link_old->get_id(), 'fake-job-old', 0 );
		$event( $link_old->get_id(), 'fake-job-old', 1 );

		// New link: 2 invocations (0,1) → creates actions for attempts 1,2.
		$event( $link_new->get_id(), 'fake-job-new', 0 );
		$event( $link_new->get_id(), 'fake-job-new', 1 );

		// Mark all as failed.
		$this->wpdb->update(
			"{$this->wpdb->prefix}actionscheduler_actions",
			array( 'status' => 'failed' ),
			array(
				'hook'   => Check_Snapshot_Status_Event::HANDLE,
				'status' => 'pending',
			)
		);

		// Set old link's actions to 2000-01-01.
		$old_args_pattern = '%"link_id":' . $link_old->get_id() . '%';
		$this->wpdb->query(
			$this->wpdb->prepare(
				"UPDATE {$this->wpdb->prefix}actionscheduler_actions SET scheduled_date_gmt = '2000-01-01 00:00:00', scheduled_date_local = '2000-01-01 00:00:00' WHERE args LIKE %s",
				$old_args_pattern
			)
		);

		// Set new link's actions to 2025-01-01.
		$new_args_pattern = '%"link_id":' . $link_new->get_id() . '%';
		$this->wpdb->query(
			$this->wpdb->prepare(
				"UPDATE {$this->wpdb->prefix}actionscheduler_actions SET scheduled_date_gmt = '2025-01-01 00:00:00', scheduled_date_local = '2025-01-01 00:00:00' WHERE args LIKE %s",
				$new_args_pattern
			)
		);

		// Run garbage collector with before date of 2020-01-01.
		$gc = new Action_Scheduler_Garbage_Collection();
		$gc->clean_check_snapshot_status_events( new \DateTimeImmutable( '2020-01-01 00:00:00' ) );

		// Get all remaining actions.
		$remaining = $this->wpdb->get_results(
			"SELECT * FROM {$this->wpdb->prefix}actionscheduler_actions WHERE hook = 'iawmlf_check_snapshot_status'"
		);

		// Old link should have 1 remaining (attempt 2 kept, attempt 1 deleted).
		// New link should have 2 remaining (untouched, outside the date range).
		$this->assertCount( 3, $remaining );

		// Build a lookup of remaining args by link_id, collecting all attempts.
		$remaining_by_link = array();
		foreach ( $remaining as $action ) {
			$args = json_decode( $action->args, true );
			$remaining_by_link[ $args['link_id'] ][] = $args['attempt'];
		}

		// Old link: only attempt 2 should remain.
		$this->assertCount( 1, $remaining_by_link[ $link_old->get_id() ] );
		$this->assertEquals( 2, $remaining_by_link[ $link_old->get_id() ][0] );

		// New link: both attempts 1 and 2 should remain (not cleaned).
		$this->assertCount( 2, $remaining_by_link[ $link_new->get_id() ] );
	}

	// ---- Create New Snapshot Event cleanup tests ----

	/**
	 * Mock the link checker client to throw so Create_New_Snapshot_Event reschedules with incremented attempt.
	 *
	 * @return void
	 */
	private function set_link_checker_to_throw(): void {
		$service = $this->createMock( Link_Checker_Client::class );
		$service->method( 'get_final_url' )
			->willThrowException( new Service_Offline_Exception( 'Service is offline' ) );

		add_filter( 'iawmlf_link_checker_client', fn() => $service );
	}

	/**
	 * @testdox When cleaning up create new snapshot events, only the last attempt per link_id should remain.
	 *
	 * @return void
	 */
	public function test_clean_create_new_snapshot_events_keeps_last_attempt(): void {
		$this->set_link_checker_to_throw();

		$link_a = $this->link_repository->upsert( new Link( 'https://example.com' ) );
		$link_b = $this->link_repository->upsert( new Link( 'https://example.org' ) );
		$link_c = $this->link_repository->upsert( new Link( 'https://example.net' ) );

		$event = new Create_New_Snapshot_Event();

		// Link A: 3 invocations (0,1,2) → creates actions for attempts 1,2,3.
		try { $event( $link_a->get_id(), 0 ); } catch ( \Throwable $th ) {} // phpcs:ignore
		try { $event( $link_a->get_id(), 1 ); } catch ( \Throwable $th ) {} // phpcs:ignore
		try { $event( $link_a->get_id(), 2 ); } catch ( \Throwable $th ) {} // phpcs:ignore

		// Link B: 2 invocations (0,1) → creates actions for attempts 1,2.
		try { $event( $link_b->get_id(), 0 ); } catch ( \Throwable $th ) {} // phpcs:ignore
		try { $event( $link_b->get_id(), 1 ); } catch ( \Throwable $th ) {} // phpcs:ignore

		// Link C: 1 invocation (0) → creates action for attempt 1.
		try { $event( $link_c->get_id(), 0 ); } catch ( \Throwable $th ) {} // phpcs:ignore

		// Mark all as failed.
		$this->wpdb->update(
			"{$this->wpdb->prefix}actionscheduler_actions",
			array( 'status' => 'failed' ),
			array(
				'hook'   => Create_New_Snapshot_Event::HANDLE,
				'status' => 'pending',
			)
		);

		// Run the garbage collector.
		$gc = new Action_Scheduler_Garbage_Collection();
		$gc->clean_create_new_snapshot_events();

		// Should have 3 actions remaining (one per link_id).
		$remaining = $this->wpdb->get_results(
			"SELECT * FROM {$this->wpdb->prefix}actionscheduler_actions WHERE hook = 'iawmlf_create_new_snapshot'"
		);
		$this->assertCount( 3, $remaining );

		// Build a lookup of remaining args by link_id.
		$remaining_by_link = array();
		foreach ( $remaining as $action ) {
			$args = json_decode( $action->args, true );
			$remaining_by_link[ $args['link_id'] ] = $args;
		}

		// Link A: should keep attempt 3.
		$this->assertEquals( 3, $remaining_by_link[ $link_a->get_id() ]['attempt'] );

		// Link B: should keep attempt 2.
		$this->assertEquals( 2, $remaining_by_link[ $link_b->get_id() ]['attempt'] );

		// Link C: should keep attempt 1 (only one in group, untouched).
		$this->assertEquals( 1, $remaining_by_link[ $link_c->get_id() ]['attempt'] );
	}

	/**
	 * @testdox When there are no failed create new snapshot actions, the garbage collector should do nothing.
	 *
	 * @return void
	 */
	public function test_clean_create_new_snapshot_events_empty_table_does_nothing(): void {
		$gc = new Action_Scheduler_Garbage_Collection();
		$gc->clean_create_new_snapshot_events();

		$remaining = $this->wpdb->get_results(
			"SELECT * FROM {$this->wpdb->prefix}actionscheduler_actions WHERE hook = 'iawmlf_create_new_snapshot'"
		);
		$this->assertCount( 0, $remaining );
	}

	/**
	 * @testdox When a before date is passed to create snapshot cleanup, only old actions should be cleaned.
	 *
	 * @return void
	 */
	public function test_clean_create_new_snapshot_events_before_date_only_cleans_old(): void {
		$this->set_link_checker_to_throw();

		$link_old = $this->link_repository->upsert( new Link( 'https://old-example.com' ) );
		$link_new = $this->link_repository->upsert( new Link( 'https://new-example.com' ) );

		$event = new Create_New_Snapshot_Event();

		// Old link: 2 invocations (0,1) → creates actions for attempts 1,2.
		try { $event( $link_old->get_id(), 0 ); } catch ( \Throwable $th ) {} // phpcs:ignore
		try { $event( $link_old->get_id(), 1 ); } catch ( \Throwable $th ) {} // phpcs:ignore

		// New link: 2 invocations (0,1) → creates actions for attempts 1,2.
		try { $event( $link_new->get_id(), 0 ); } catch ( \Throwable $th ) {} // phpcs:ignore
		try { $event( $link_new->get_id(), 1 ); } catch ( \Throwable $th ) {} // phpcs:ignore

		// Mark all as failed.
		$this->wpdb->update(
			"{$this->wpdb->prefix}actionscheduler_actions",
			array( 'status' => 'failed' ),
			array(
				'hook'   => Create_New_Snapshot_Event::HANDLE,
				'status' => 'pending',
			)
		);

		// Set old link's actions to 2000-01-01.
		$old_args_pattern = '%"link_id":' . $link_old->get_id() . '%';
		$this->wpdb->query(
			$this->wpdb->prepare(
				"UPDATE {$this->wpdb->prefix}actionscheduler_actions SET scheduled_date_gmt = '2000-01-01 00:00:00', scheduled_date_local = '2000-01-01 00:00:00' WHERE args LIKE %s",
				$old_args_pattern
			)
		);

		// Set new link's actions to 2025-01-01.
		$new_args_pattern = '%"link_id":' . $link_new->get_id() . '%';
		$this->wpdb->query(
			$this->wpdb->prepare(
				"UPDATE {$this->wpdb->prefix}actionscheduler_actions SET scheduled_date_gmt = '2025-01-01 00:00:00', scheduled_date_local = '2025-01-01 00:00:00' WHERE args LIKE %s",
				$new_args_pattern
			)
		);

		// Run garbage collector with before date of 2020-01-01.
		$gc = new Action_Scheduler_Garbage_Collection();
		$gc->clean_create_new_snapshot_events( new \DateTimeImmutable( '2020-01-01 00:00:00' ) );

		// Get all remaining actions.
		$remaining = $this->wpdb->get_results(
			"SELECT * FROM {$this->wpdb->prefix}actionscheduler_actions WHERE hook = 'iawmlf_create_new_snapshot'"
		);

		// Old link: 1 remaining (attempt 2 kept). New link: 2 remaining (untouched).
		$this->assertCount( 3, $remaining );

		// Build a lookup of remaining args by link_id, collecting all attempts.
		$remaining_by_link = array();
		foreach ( $remaining as $action ) {
			$args = json_decode( $action->args, true );
			$remaining_by_link[ $args['link_id'] ][] = $args['attempt'];
		}

		// Old link: only attempt 2 should remain.
		$this->assertCount( 1, $remaining_by_link[ $link_old->get_id() ] );
		$this->assertEquals( 2, $remaining_by_link[ $link_old->get_id() ][0] );

		// New link: both attempts 1 and 2 should remain (not cleaned).
		$this->assertCount( 2, $remaining_by_link[ $link_new->get_id() ] );
	}

	// ---- Update Archive URL Event cleanup tests ----

	/**
	 * Mock the snapshot client so find_archive() returns null, causing Update_Archive_URL_Event to reschedule.
	 *
	 * @return void
	 */
	private function set_snapshot_client_no_archive(): void {
		$service = $this->createMock( Snapshot_Client::class );
		$service->method( 'get_latest_snapshot' )->willReturn( null );

		add_filter( 'iawmlf_snapshot_client', fn() => $service );
	}

	/**
	 * @testdox When cleaning up update archive URL events, only the last attempt per link_id should remain.
	 *
	 * @return void
	 */
	public function test_clean_update_archive_url_events_keeps_last_attempt(): void {
		$this->set_snapshot_client_no_archive();

		$link_a = $this->link_repository->upsert( new Link( 'https://example.com' ) );
		$link_b = $this->link_repository->upsert( new Link( 'https://example.org' ) );
		$link_c = $this->link_repository->upsert( new Link( 'https://example.net' ) );

		$event = new Update_Archive_URL_Event();

		// Link A: 3 invocations (0,1,2) → creates actions for attempts 1,2,3.
		$event( $link_a->get_id(), 0 );
		$event( $link_a->get_id(), 1 );
		$event( $link_a->get_id(), 2 );

		// Link B: 2 invocations (0,1) → creates actions for attempts 1,2.
		$event( $link_b->get_id(), 0 );
		$event( $link_b->get_id(), 1 );

		// Link C: 1 invocation (0) → creates action for attempt 1.
		$event( $link_c->get_id(), 0 );

		// Mark all as failed.
		$this->wpdb->update(
			"{$this->wpdb->prefix}actionscheduler_actions",
			array( 'status' => 'failed' ),
			array(
				'hook'   => Update_Archive_URL_Event::HANDLE,
				'status' => 'pending',
			)
		);

		// Run the garbage collector.
		$gc = new Action_Scheduler_Garbage_Collection();
		$gc->clean_update_archive_url_events();

		// Should have 3 actions remaining (one per link_id).
		$remaining = $this->wpdb->get_results(
			"SELECT * FROM {$this->wpdb->prefix}actionscheduler_actions WHERE hook = 'iawmlf_update_archive_url'"
		);
		$this->assertCount( 3, $remaining );

		// Build a lookup of remaining args by link_id.
		$remaining_by_link = array();
		foreach ( $remaining as $action ) {
			$args = json_decode( $action->args, true );
			$remaining_by_link[ $args['link_id'] ] = $args;
		}

		// Link A: should keep attempt 3.
		$this->assertEquals( 3, $remaining_by_link[ $link_a->get_id() ]['attempt'] );

		// Link B: should keep attempt 2.
		$this->assertEquals( 2, $remaining_by_link[ $link_b->get_id() ]['attempt'] );

		// Link C: should keep attempt 1 (only one in group, untouched).
		$this->assertEquals( 1, $remaining_by_link[ $link_c->get_id() ]['attempt'] );
	}

	/**
	 * @testdox When there are no failed update archive URL actions, the garbage collector should do nothing.
	 *
	 * @return void
	 */
	public function test_clean_update_archive_url_events_empty_table_does_nothing(): void {
		$gc = new Action_Scheduler_Garbage_Collection();
		$gc->clean_update_archive_url_events();

		$remaining = $this->wpdb->get_results(
			"SELECT * FROM {$this->wpdb->prefix}actionscheduler_actions WHERE hook = 'iawmlf_update_archive_url'"
		);
		$this->assertCount( 0, $remaining );
	}

	/**
	 * @testdox When each group only has a single update archive URL action, nothing should be deleted.
	 *
	 * @return void
	 */
	public function test_clean_update_archive_url_events_single_attempt_not_deleted(): void {
		$this->set_snapshot_client_no_archive();

		$link_a = $this->link_repository->upsert( new Link( 'https://example.com' ) );
		$link_b = $this->link_repository->upsert( new Link( 'https://example.org' ) );

		$event = new Update_Archive_URL_Event();

		// Each link gets a single invocation → 1 action each.
		$event( $link_a->get_id(), 0 );
		$event( $link_b->get_id(), 0 );

		// Mark as failed.
		$this->wpdb->update(
			"{$this->wpdb->prefix}actionscheduler_actions",
			array( 'status' => 'failed' ),
			array(
				'hook'   => Update_Archive_URL_Event::HANDLE,
				'status' => 'pending',
			)
		);

		// Run the garbage collector.
		$gc = new Action_Scheduler_Garbage_Collection();
		$gc->clean_update_archive_url_events();

		// Both actions should still exist.
		$remaining = $this->wpdb->get_results(
			"SELECT * FROM {$this->wpdb->prefix}actionscheduler_actions WHERE hook = 'iawmlf_update_archive_url'"
		);
		$this->assertCount( 2, $remaining );
	}

	/**
	 * @testdox When a before date is passed to update archive URL cleanup, only old actions should be cleaned.
	 *
	 * @return void
	 */
	public function test_clean_update_archive_url_events_before_date_only_cleans_old(): void {
		$this->set_snapshot_client_no_archive();

		$link_old = $this->link_repository->upsert( new Link( 'https://old-example.com' ) );
		$link_new = $this->link_repository->upsert( new Link( 'https://new-example.com' ) );

		$event = new Update_Archive_URL_Event();

		// Old link: 2 invocations (0,1) → creates actions for attempts 1,2.
		$event( $link_old->get_id(), 0 );
		$event( $link_old->get_id(), 1 );

		// New link: 2 invocations (0,1) → creates actions for attempts 1,2.
		$event( $link_new->get_id(), 0 );
		$event( $link_new->get_id(), 1 );

		// Mark all as failed.
		$this->wpdb->update(
			"{$this->wpdb->prefix}actionscheduler_actions",
			array( 'status' => 'failed' ),
			array(
				'hook'   => Update_Archive_URL_Event::HANDLE,
				'status' => 'pending',
			)
		);

		// Set old link's actions to 2000-01-01.
		$old_args_pattern = '%"link_id":' . $link_old->get_id() . '%';
		$this->wpdb->query(
			$this->wpdb->prepare(
				"UPDATE {$this->wpdb->prefix}actionscheduler_actions SET scheduled_date_gmt = '2000-01-01 00:00:00', scheduled_date_local = '2000-01-01 00:00:00' WHERE args LIKE %s",
				$old_args_pattern
			)
		);

		// Set new link's actions to 2025-01-01.
		$new_args_pattern = '%"link_id":' . $link_new->get_id() . '%';
		$this->wpdb->query(
			$this->wpdb->prepare(
				"UPDATE {$this->wpdb->prefix}actionscheduler_actions SET scheduled_date_gmt = '2025-01-01 00:00:00', scheduled_date_local = '2025-01-01 00:00:00' WHERE args LIKE %s",
				$new_args_pattern
			)
		);

		// Run garbage collector with before date of 2020-01-01.
		$gc = new Action_Scheduler_Garbage_Collection();
		$gc->clean_update_archive_url_events( new \DateTimeImmutable( '2020-01-01 00:00:00' ) );

		// Get all remaining actions.
		$remaining = $this->wpdb->get_results(
			"SELECT * FROM {$this->wpdb->prefix}actionscheduler_actions WHERE hook = 'iawmlf_update_archive_url'"
		);

		// Old link: 1 remaining (attempt 2 kept). New link: 2 remaining (untouched).
		$this->assertCount( 3, $remaining );

		// Build a lookup of remaining args by link_id, collecting all attempts.
		$remaining_by_link = array();
		foreach ( $remaining as $action ) {
			$args = json_decode( $action->args, true );
			$remaining_by_link[ $args['link_id'] ][] = $args['attempt'];
		}

		// Old link: only attempt 2 should remain.
		$this->assertCount( 1, $remaining_by_link[ $link_old->get_id() ] );
		$this->assertEquals( 2, $remaining_by_link[ $link_old->get_id() ][0] );

		// New link: both attempts 1 and 2 should remain (not cleaned).
		$this->assertCount( 2, $remaining_by_link[ $link_new->get_id() ] );
	}

	// ---- Check Validator Status Event cleanup tests ----

	/**
	 * @testdox When cleaning up check validator status events, only the last attempt per job_id+link_id should remain.
	 *
	 * @return void
	 */
	public function test_clean_check_validator_status_events_keeps_last_attempt(): void {
		$this->set_snapshot_client_response( array( 'status' => 'pending' ) );

		$link_a = $this->link_repository->upsert( new Link( 'https://example.com' ) );
		$link_b = $this->link_repository->upsert( new Link( 'https://example.org' ) );
		$link_c = $this->link_repository->upsert( new Link( 'https://example.net' ) );

		$event = new Check_Validator_Status();

		// Link A with job-aaa: 2 invocations (0,1) → creates actions for attempts 1,2.
		$event( $link_a->get_id(), 'fake-job-aaa', 0 );
		$event( $link_a->get_id(), 'fake-job-aaa', 1 );

		// Link B with job-bbb: 2 invocations (0,1) → creates actions for attempts 1,2.
		$event( $link_b->get_id(), 'fake-job-bbb', 0 );
		$event( $link_b->get_id(), 'fake-job-bbb', 1 );

		// Link C with job-aaa (same job as A, different link): 1 invocation (0) → creates action for attempt 1.
		$event( $link_c->get_id(), 'fake-job-aaa', 0 );

		// Mark all as failed.
		$this->wpdb->update(
			"{$this->wpdb->prefix}actionscheduler_actions",
			array( 'status' => 'failed' ),
			array(
				'hook'   => Check_Validator_Status::HANDLE,
				'status' => 'pending',
			)
		);

		// Run the garbage collector.
		$gc = new Action_Scheduler_Garbage_Collection();
		$gc->clean_check_validator_status_events();

		// Should have 3 actions remaining (one per job_id + link_id group).
		$remaining = $this->wpdb->get_results(
			"SELECT * FROM {$this->wpdb->prefix}actionscheduler_actions WHERE hook = 'iawmlf_check_validator_status'"
		);
		$this->assertCount( 3, $remaining );

		// Build a lookup of remaining args by link_id.
		$remaining_by_link = array();
		foreach ( $remaining as $action ) {
			$args = json_decode( $action->args, true );
			$remaining_by_link[ $args['link_id'] ] = $args;
		}

		// Link A group: should keep attempt 2.
		$this->assertEquals( 2, $remaining_by_link[ $link_a->get_id() ]['attempt'] );
		$this->assertEquals( 'fake-job-aaa', $remaining_by_link[ $link_a->get_id() ]['job_id'] );

		// Link B group: should keep attempt 2.
		$this->assertEquals( 2, $remaining_by_link[ $link_b->get_id() ]['attempt'] );
		$this->assertEquals( 'fake-job-bbb', $remaining_by_link[ $link_b->get_id() ]['job_id'] );

		// Link C group: should keep attempt 1 (only one in group, untouched).
		$this->assertEquals( 1, $remaining_by_link[ $link_c->get_id() ]['attempt'] );
		$this->assertEquals( 'fake-job-aaa', $remaining_by_link[ $link_c->get_id() ]['job_id'] );
	}

	/**
	 * @testdox When there are no failed check validator status actions, the garbage collector should do nothing.
	 *
	 * @return void
	 */
	public function test_clean_check_validator_status_events_empty_table_does_nothing(): void {
		$gc = new Action_Scheduler_Garbage_Collection();
		$gc->clean_check_validator_status_events();

		$remaining = $this->wpdb->get_results(
			"SELECT * FROM {$this->wpdb->prefix}actionscheduler_actions WHERE hook = 'iawmlf_check_validator_status'"
		);
		$this->assertCount( 0, $remaining );
	}

	/**
	 * @testdox When each group only has a single check validator status action, nothing should be deleted.
	 *
	 * @return void
	 */
	public function test_clean_check_validator_status_events_single_attempt_not_deleted(): void {
		$this->set_snapshot_client_response( array( 'status' => 'pending' ) );

		$link_a = $this->link_repository->upsert( new Link( 'https://example.com' ) );
		$link_b = $this->link_repository->upsert( new Link( 'https://example.org' ) );

		$event = new Check_Validator_Status();

		// Each link gets a single invocation → 1 action each.
		$event( $link_a->get_id(), 'fake-job-aaa', 0 );
		$event( $link_b->get_id(), 'fake-job-bbb', 0 );

		// Mark as failed.
		$this->wpdb->update(
			"{$this->wpdb->prefix}actionscheduler_actions",
			array( 'status' => 'failed' ),
			array(
				'hook'   => Check_Validator_Status::HANDLE,
				'status' => 'pending',
			)
		);

		// Run the garbage collector.
		$gc = new Action_Scheduler_Garbage_Collection();
		$gc->clean_check_validator_status_events();

		// Both actions should still exist.
		$remaining = $this->wpdb->get_results(
			"SELECT * FROM {$this->wpdb->prefix}actionscheduler_actions WHERE hook = 'iawmlf_check_validator_status'"
		);
		$this->assertCount( 2, $remaining );
	}

	/**
	 * @testdox When a before date is passed to check validator status cleanup, only old actions should be cleaned.
	 *
	 * @return void
	 */
	public function test_clean_check_validator_status_events_before_date_only_cleans_old(): void {
		$this->set_snapshot_client_response( array( 'status' => 'pending' ) );

		$link_old = $this->link_repository->upsert( new Link( 'https://old-example.com' ) );
		$link_new = $this->link_repository->upsert( new Link( 'https://new-example.com' ) );

		$event = new Check_Validator_Status();

		// Old link: 2 invocations (0,1) → creates actions for attempts 1,2.
		$event( $link_old->get_id(), 'fake-job-old', 0 );
		$event( $link_old->get_id(), 'fake-job-old', 1 );

		// New link: 2 invocations (0,1) → creates actions for attempts 1,2.
		$event( $link_new->get_id(), 'fake-job-new', 0 );
		$event( $link_new->get_id(), 'fake-job-new', 1 );

		// Mark all as failed.
		$this->wpdb->update(
			"{$this->wpdb->prefix}actionscheduler_actions",
			array( 'status' => 'failed' ),
			array(
				'hook'   => Check_Validator_Status::HANDLE,
				'status' => 'pending',
			)
		);

		// Set old link's actions to 2000-01-01.
		$old_args_pattern = '%"link_id":' . $link_old->get_id() . '%';
		$this->wpdb->query(
			$this->wpdb->prepare(
				"UPDATE {$this->wpdb->prefix}actionscheduler_actions SET scheduled_date_gmt = '2000-01-01 00:00:00', scheduled_date_local = '2000-01-01 00:00:00' WHERE args LIKE %s",
				$old_args_pattern
			)
		);

		// Set new link's actions to 2025-01-01.
		$new_args_pattern = '%"link_id":' . $link_new->get_id() . '%';
		$this->wpdb->query(
			$this->wpdb->prepare(
				"UPDATE {$this->wpdb->prefix}actionscheduler_actions SET scheduled_date_gmt = '2025-01-01 00:00:00', scheduled_date_local = '2025-01-01 00:00:00' WHERE args LIKE %s",
				$new_args_pattern
			)
		);

		// Run garbage collector with before date of 2020-01-01.
		$gc = new Action_Scheduler_Garbage_Collection();
		$gc->clean_check_validator_status_events( new \DateTimeImmutable( '2020-01-01 00:00:00' ) );

		// Get all remaining actions.
		$remaining = $this->wpdb->get_results(
			"SELECT * FROM {$this->wpdb->prefix}actionscheduler_actions WHERE hook = 'iawmlf_check_validator_status'"
		);

		// Old link: 1 remaining (attempt 2 kept). New link: 2 remaining (untouched).
		$this->assertCount( 3, $remaining );

		// Build a lookup of remaining args by link_id, collecting all attempts.
		$remaining_by_link = array();
		foreach ( $remaining as $action ) {
			$args = json_decode( $action->args, true );
			$remaining_by_link[ $args['link_id'] ][] = $args['attempt'];
		}

		// Old link: only attempt 2 should remain.
		$this->assertCount( 1, $remaining_by_link[ $link_old->get_id() ] );
		$this->assertEquals( 2, $remaining_by_link[ $link_old->get_id() ][0] );

		// New link: both attempts 1 and 2 should remain (not cleaned).
		$this->assertCount( 2, $remaining_by_link[ $link_new->get_id() ] );
	}
}
