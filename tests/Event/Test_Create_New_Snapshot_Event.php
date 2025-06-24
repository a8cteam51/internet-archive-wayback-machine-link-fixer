<?php

/**
 * Tests for Create New Snapshot Event
 *
 * @since 1.3.0
 *
 * @coversDefaultClass \WPCOMSpecialProjects\Wayback_Link_Fixer\Event\Create_New_Snapshot_Event
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Tests\Event;

use Throwable;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link_Repository;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Event\Create_New_Snapshot_Event;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Link_Checker_Client;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Snapshot_Client;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Exception\Service_Offline_Exception;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Exception\Exceeded_Snapshot_Limit_Exception;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Exception\Invalid_Response_Exception;

/**
 * Test_Create_New_Snapshot_Event
 *
 * @group Event
 * @group Create_New_Snapshot_Event
 */
class Test_Create_New_Snapshot_Event extends \WP_UnitTestCase {

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

		// Clear any filters.
		remove_all_filters( 'wlf_link_checker_client' );
		remove_all_filters( 'wlf_snapshot_client' );

		// Clear the link table.
		$this->wpdb->query( 'TRUNCATE TABLE ' . Settings::get_link_table_name() );

		parent::set_up();
	}

	/**
	 * Tear down
	 */
	public function tear_down(): void {
		// Clear filters
		remove_all_filters( 'wlf_link_checker_client' );
		remove_all_filters( 'wlf_snapshot_client' );

		parent::tear_down();
	}

	/**
	 * @testdox When max attempts are reached, the link should be marked as done and throw an exception
	 *
	 * @return void
	 */
	public function test_max_attempts_marks_as_done_and_throws(): void {
		$link = $this->link_repository->upsert( new Link( 'https://example.com' ) );

		$event = new Create_New_Snapshot_Event();
		$event->setup();

		try {
			$event( $link->get_id(), 4 ); // Assuming max attempts is 3, we pass 4 to trigger the exception
		} catch ( \Throwable $th ) {
			$this->assertEquals( 'Max attempts reached for link ' . $link->get_id(), $th->getMessage() );
		}

		// Get the link from the repository.
		$updated_link = $this->link_repository->find_by_id( $link->get_id() );

		// Check the link is marked as done.
		$this->assertEquals( Link::PROCESS_DONE, $updated_link->get_archive_process() );
	}

	/**
	 * @testdox When link is not found, an exception should be thrown
	 *
	 * @return void
	 */
	public function test_link_not_found_throws_exception(): void {
		$event = new Create_New_Snapshot_Event();
		$event->setup();

		try {
			$event( 999, 0 );
			$this->fail( 'Exception should have been thrown' );
		} catch ( \Throwable $th ) {
			$this->assertEquals( 'Link not found with id 999', $th->getMessage() );
		}
	}


	/**
	 * @testdox When final URL is different from original, the link should be updated with redirect URL
	 *
	 * @return void
	 */
	public function test_different_final_url_updates_redirect(): void {
		$original_url = 'https://example.com';
		$final_url    = 'https://example.com/final';

		// Create the link first
		$link = $this->link_repository->upsert( new Link( $original_url ) );

		// Setup the mock service
		$service = $this->createMock( Link_Checker_Client::class );
		$service->method( 'get_final_url' )->willReturn( $final_url );
		add_filter( 'wlf_link_checker_client', fn() => $service );

		$event = new Create_New_Snapshot_Event();
		$event->setup();

		try {
			$event( $link->get_id(), 0 );
		} catch ( \Throwable $th ) {
			// We expect an exception later in the process when trying to create snapshot
		}

		// Get the updated link from the repository
		$updated_link = $this->link_repository->find_by_id( $link->get_id() );

		// The redirect href should be set to the final URL
		$this->assertEquals( $final_url, $updated_link->get_redirect_href() );
		$this->assertEquals( $original_url, $updated_link->get_href() );
		$this->assertEquals( Link::PROCESS_PENDING, $updated_link->get_archive_process() );
	}

	/**
	 * @testdox When get_final_url throws an exception, the event should be rescheduled with incremented attempt count
	 *
	 * @return void
	 */
	public function test_get_final_url_error_reschedules_with_incremented_attempt(): void {
		$url     = 'https://example.com';
		$attempt = 1;

		// Create the link first
		$link = $this->link_repository->upsert( new Link( $url ) );

		// Setup the mock service to throw an exception
		$service = $this->createMock( Link_Checker_Client::class );
		$service->method( 'get_final_url' )
			->willThrowException( new Service_Offline_Exception( 'Service is offline' ) );
		add_filter( 'wlf_link_checker_client', fn() => $service );

		$event = new Create_New_Snapshot_Event();
		$event->setup();

		try {
			$event( $link->get_id(), $attempt );
			$this->fail( 'Exception should have been thrown' );
		} catch ( \Throwable $th ) {
			$this->assertEquals( 'Error getting final URL for link id: ' . $link->get_id() . ', error: Service is offline', $th->getMessage() );
		}

		// Get the updated link from the repository
		$updated_link = $this->link_repository->find_by_id( $link->get_id() );

		// Verify the link is still pending
		$this->assertEquals( Link::PROCESS_PENDING, $updated_link->get_archive_process() );

		// Check that a new event was scheduled with incremented attempt
		$actions = $this->wpdb->get_results( "SELECT * FROM {$this->wpdb->prefix}actionscheduler_actions" );
		$this->assertCount( 1, $actions );
		$this->assertEquals( 'wlf_create_new_snapshot', $actions[0]->hook );
		$this->assertEquals(
			json_encode(
				array(
					'link_id' => $link->get_id(),
					'attempt' => $attempt + 1,
				)
			),
			$actions[0]->args
		);
	}

	/**
	 * @testdox When create_snapshot throws a non-limit exception and not the last attempt, it should reschedule
	 *
	 * @return void
	 */
	public function test_create_snapshot_error_reschedules_if_not_last_attempt(): void {
		$url     = 'https://example.com';
		$attempt = 1;

		// Create the link first
		$link = $this->link_repository->upsert( new Link( $url ) );

		// Setup the link checker mock to return the same URL (no redirect)
		$link_checker = $this->createMock( Link_Checker_Client::class );
		$link_checker->method( 'get_final_url' )->willReturn( $url );
		add_filter( 'wlf_link_checker_client', fn() => $link_checker );

		// Setup the snapshot client mock to throw a service offline exception
		$snapshot_client = $this->createMock( Snapshot_Client::class );
		$snapshot_client->method( 'create_snapshot' )
			->willThrowException( new Service_Offline_Exception( 'Service is offline' ) );
		add_filter( 'wlf_snapshot_client', fn() => $snapshot_client );

		$event = new Create_New_Snapshot_Event();
		$event->setup();

		try {
			$event( $link->get_id(), $attempt );
		} catch ( Service_Offline_Exception $th ) {
			$this->assertEquals( 'Service is offline', $th->getMessage() );
		}

		// Get the updated link from the repository
		$updated_link = $this->link_repository->find_by_id( $link->get_id() );

		// Verify the link is still pending
		$this->assertEquals( Link::PROCESS_PENDING, $updated_link->get_archive_process() );

		// Check that a new event was scheduled with incremented attempt
		$actions = $this->wpdb->get_results( "SELECT * FROM {$this->wpdb->prefix}actionscheduler_actions" );
		$this->assertCount( 1, $actions );
		$this->assertEquals( 'wlf_create_new_snapshot', $actions[0]->hook );
		$this->assertEquals(
			json_encode(
				array(
					'link_id' => $link->get_id(),
					'attempt' => $attempt + 1,
				)
			),
			$actions[0]->args
		);
	}

	/**
	 * @testdox When create_snapshot throws Exceeded_Snapshot_Limit_Exception, it should reschedule with 24hr delay
	 *
	 * @return void
	 */
	public function test_exceeded_snapshot_limit_reschedules_with_24hr_delay(): void {
		$url     = 'https://example.com';
		$attempt = 1;

		// Create the link first
		$link = $this->link_repository->upsert( new Link( $url ) );

		// Setup the link checker mock to return the same URL (no redirect)
		$link_checker = $this->createMock( Link_Checker_Client::class );
		$link_checker->method( 'get_final_url' )->willReturn( $url );
		add_filter( 'wlf_link_checker_client', fn() => $link_checker );

		// Setup the snapshot client mock to throw an exceeded limit exception
		$snapshot_client = $this->createMock( Snapshot_Client::class );
		$snapshot_client->method( 'create_snapshot' )
			->willThrowException( new Exceeded_Snapshot_Limit_Exception() );
		add_filter( 'wlf_snapshot_client', fn() => $snapshot_client );

		$event = new Create_New_Snapshot_Event();
		$event->setup();

		$event( $link->get_id(), $attempt );

		// Get the updated link from the repository
		$updated_link = $this->link_repository->find_by_id( $link->get_id() );

		// Verify the link stays as pending
		$this->assertEquals( Link::PROCESS_PENDING, $updated_link->get_archive_process() );

		// Check that a new event was scheduled with same attempt count but 24hr delay
		$actions = $this->wpdb->get_results( "SELECT * FROM {$this->wpdb->prefix}actionscheduler_actions" );
		$this->assertCount( 1, $actions );
		$this->assertEquals( 'wlf_create_new_snapshot', $actions[0]->hook );
		$this->assertEquals(
			json_encode(
				array(
					'link_id' => $link->get_id(),
					'attempt' => $attempt + 1, // Increment attempt count
				)
			),
			$actions[0]->args
		);

		// Verify the scheduled time is roughly 24 hours in the future
		$scheduled_time = strtotime( $actions[0]->scheduled_date_gmt );
		$expected_time  = time() + DAY_IN_SECONDS;
		$this->assertEqualsWithDelta( $expected_time, $scheduled_time, 5, 'Scheduled time should be roughly 24 hours in the future' );
	}

	/**
	 * @testdox When create_snapshot throws any exception on last attempt, it should not reschedule
	 *
	 * @return void
	 */
	public function test_create_snapshot_error_does_not_reschedule_on_last_attempt(): void {
		$url          = 'https://example.com';
		$max_attempts = 3;

		// Create the link first
		$link = $this->link_repository->upsert( new Link( $url ) );

		// Setup the link checker mock to return the same URL (no redirect)
		$link_checker = $this->createMock( Link_Checker_Client::class );
		$link_checker->method( 'get_final_url' )->willReturn( $url );
		add_filter( 'wlf_link_checker_client', fn() => $link_checker );

		// Setup the snapshot client mock to throw an invalid response exception
		$snapshot_client = $this->createMock( Snapshot_Client::class );
		$snapshot_client->method( 'create_snapshot' )
			->willThrowException( new Invalid_Response_Exception( 'Invalid response' ) );
		add_filter( 'wlf_snapshot_client', fn() => $snapshot_client );

		$event = new Create_New_Snapshot_Event();
		$event->setup();

		try {
			$event( $link->get_id(), $max_attempts );
		} catch ( Throwable $th ) {
			// Error Error creating snapshot (Last attempt) for link id: 1, error: Invalid response
			$this->assertInstanceOf( Throwable::class, $th );
			$this->assertEquals(
				sprintf(
					'Error creating snapshot (Last attempt) for link id: %d, error: %s',
					$link->get_id(),
					'Invalid response'
				),
				$th->getMessage()
			);
		}

		// Get the updated link from the repository
		$updated_link = $this->link_repository->find_by_id( $link->get_id() );

		// Verify the link stays as pending
		$this->assertEquals( Link::PROCESS_DONE, $updated_link->get_archive_process() );

		// Verify no new events were scheduled
		$actions = $this->wpdb->get_results( "SELECT * FROM {$this->wpdb->prefix}actionscheduler_actions" );
		$this->assertEmpty( $actions );
	}
}
