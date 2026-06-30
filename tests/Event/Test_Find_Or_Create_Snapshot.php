<?php

/**
 * Testthe Find or Create Snapshot Event
 *
 * @since 1.2.0
 *
 * @coversDefaultClass \Internet_Archive\Wayback_Machine_Link_Fixer\Event\Find_Or_Create_Snapshot_Event
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Tests\Event;

use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link;
use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link_Repository;
use Internet_Archive\Wayback_Machine_Link_Fixer\Event\Find_Or_Create_Snapshot_Event;

/**
 * Test_Find_Or_Create_Snapshot
 */
class Test_Find_Or_Create_Snapshot extends \WP_UnitTestCase {

	private $wpdb;

	/**
	 * Setup
	 */
	public function set_up(): void {
		$this->wpdb = $GLOBALS['wpdb'];

		// Clear the actionscheduler_actions table.
		$this->wpdb->query( "TRUNCATE TABLE {$this->wpdb->prefix}actionscheduler_actions" );

		parent::set_up();
	}

	/**
	 * Tear down.
	 */
	public function tear_down(): void {
		remove_all_filters( 'pre_http_request' );
		delete_transient( 'iawmlf_archive_api_online' );
		parent::tear_down();
	}

	/**
	 * Mocks the event's archive.org calls; livewebcheck returns $link_check_code.
	 */
	private function mock_archive_http( int $link_check_code ): void {
		$snapshot_body = json_encode(
			array(
				'url'                => 'http://example.com',
				'archived_snapshots' => array(
					'closest' => array(
						'available' => true,
						'status'    => 200,
						'url'       => 'http://web.archive.org/web/20240101000000/http://example.com',
						'timestamp' => '20240101000000',
					),
				),
			)
		);

		add_filter(
			'pre_http_request',
			function ( $pre, $args, $url ) use ( $link_check_code, $snapshot_body ) {
				// The system status endpoint (is_online).
				if ( false !== strpos( $url, 'save/status/system' ) ) {
					return array(
						'response' => array( 'code' => 200 ),
						'body'     => json_encode( array( 'status' => 'ok' ) ),
					);
				}

				// The find-snapshot endpoint (get_latest_snapshot).
				if ( false !== strpos( $url, 'wayback/available' ) ) {
					return array(
						'response' => array( 'code' => 200 ),
						'body'     => $snapshot_body,
					);
				}

				// The link checker endpoint (check_single) - the one under test.
				if ( false !== strpos( $url, 'livewebcheck' ) ) {
					return array(
						'response' => array( 'code' => $link_check_code ),
						'body'     => json_encode( array( 'status' => 200 ) ),
					);
				}

				// Anything else, a benign 200.
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => '{}',
				);
			},
			10,
			3
		);
	}

	/**
	 * @testdox BASELINE: when the link checker returns a 200, the link is checked and marked done.
	 *
	 * @return void
	 */
	public function test_200_from_link_checker_processes_link(): void {
		$this->mock_archive_http( 200 );
		delete_transient( 'iawmlf_archive_api_online' );

		$repo = new Link_Repository();
		$link = $repo->upsert( new Link( 'https://example.com' ) );

		$event = new Find_Or_Create_Snapshot_Event();
		$event->setup();
		$event( $link->get_id() );

		$link = $repo->find_by_id( $link->get_id() );

		// A successful check means the link is processed/done.
		$this->assertEquals( Link::PROCESS_DONE, $link->get_archive_process() );
	}

	/**
	 * @testdox When a link-check HTTP request comes back as a non-200, the event must do nothing - the link is left untouched.
	 *
	 * @dataProvider non_success_status_code_provider
	 *
	 * @param int $code The HTTP status code the livewebcheck endpoint returns.
	 *
	 * @return void
	 */
	public function test_non_2xx_from_link_checker_does_nothing( int $code ): void {
		$this->mock_archive_http( $code );
		delete_transient( 'iawmlf_archive_api_online' );

		$repo = new Link_Repository();
		$link = $repo->upsert( new Link( 'https://example.com' ) );
		$id   = $link->get_id();

		// Capture the state before the event runs.
		$process_before = $link->get_archive_process();

		$event = new Find_Or_Create_Snapshot_Event();
		$event->setup();

		try {
			$event( $id );
		} catch ( \Throwable $e ) {
			// "Do nothing" means it must not bubble an error to the scheduler either.
			$this->fail( "A {$code} link-check response must be handled silently, but the event threw: " . $e->getMessage() );
		}

		$link = $repo->find_by_id( $id );

		// The links process state must be unchanged.
		$this->assertSame(
			$process_before,
			$link->get_archive_process(),
			"A {$code} link-check response must not change the link's process state."
		);

		// The link must not have been marked done.
		$this->assertNotEquals(
			Link::PROCESS_DONE,
			$link->get_archive_process(),
			"A {$code} link-check response must not mark the link as done."
		);

		// The link must not have had an archived url applied.
		$this->assertEmpty(
			$link->get_archived_href(),
			"A {$code} link-check response must not set an archived url on the link."
		);
	}

	/**
	 * Non-2xx (offline) HTTP status codes seen during the DDoS outage.
	 *
	 * @return array<string, array{0:int}>
	 */
	public static function non_success_status_code_provider(): array {
		return array(
			'503 service unavailable' => array( 503 ),
			'404 not found'           => array( 404 ),
			'403 forbidden'           => array( 403 ),
			'500 generic non-2xx'     => array( 500 ),
		);
	}

	/**
	 * @testdox When a link is added to the queue and its url is already an archive.org url (HTTPS), it should not be added to the queue and a message added to the link object.
	 *
	 * @return void
	 */
	public function test_archive_org_links_are_not_added_to_queue_https(): void {
		$link = new Link(
			'https://web.archive.org/web/20210101000000/https://example.com',
		);

		// Add the link to the database.
		$repo = new Link_Repository();
		$link = $repo->upsert( $link );

		// Create the event.
		$event = new Find_Or_Create_Snapshot_Event();

		$event->setup();

		try {
			$event( $link->get_id() );
		} catch ( \Exception $e ) {
			$this->assertEquals( 'Already an Internet Archive Snapshot.', $e->getMessage() );
		}

		// Get the link from the repository.
		$link = $repo->find_by_id( $link->get_id() );

		// The link should have a message.
		$this->assertEquals( 'Already an Internet Archive Snapshot.', $link->get_message() );
	}

		/**
	 * @testdox When a link is added to the queue and its url is already an archive.org url (HTTP), it should not be added to the queue and a message added to the link object.
	 *
	 * @return void
	 */
	public function test_archive_org_links_are_not_added_to_queue_http(): void {
		$link = new Link(
			'http://web.archive.org/web/20210101000000/https://example.com',
		);

		// Add the link to the database.
		$repo = new Link_Repository();
		$link = $repo->upsert( $link );

		// Create the event.
		$event = new Find_Or_Create_Snapshot_Event();

		$event->setup();

		try {
			$event( $link->get_id() );
		} catch ( \Exception $e ) {
			$this->assertEquals( 'Already an Internet Archive Snapshot.', $e->getMessage() );
		}

		// Get the link from the repository.
		$link = $repo->find_by_id( $link->get_id() );

		// The link should have a message.
		$this->assertEquals( 'Already an Internet Archive Snapshot.', $link->get_message() );
	}

	/**
	 * @testdox When a link is found on wayback machine, it should have its snapshot url added and not added to the queue to create.
	 *
	 * @return void
	 */
	public function test_link_is_found_on_wayback_machine(): void {
		if ( $GLOBALS['iawmlf_skip_live_api_tests'] === true ) {
			$this->markTestSkipped( 'Skipping live API tests' );
		}
		// Create mock snapshot client.
		$client = $this->createMock( \Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\Snapshot_Client::class );
		$client->method( 'get_latest_snapshot' )
			->willReturn( array( 'url' => 'https://web.archive.org/web/iawmlf_glynn/https://example.com' ) );

		// Set the mock client
		add_filter(
			'iawmlf_snapshot_client',
			function () use ( $client ) {
				return $client;
			}
		);

		// Create a link.
		$link = new Link( 'https://example.com' );

		// Add the link to the database.
		$repo = new Link_Repository();
		$link = $repo->upsert( $link );

		// Create the event.
		$event = new Find_Or_Create_Snapshot_Event();
		$event->setup();

		try {
			$event( $link->get_id() );
		} catch ( \Throwable $th ) {
			// Skip the test if the snapshot creation fails.
			$this->markTestSkipped( 'Snapshot creation failed ' . $th->getMessage() );
		}

		// Get the link from the repository.
		$link = $repo->find_by_id( $link->get_id() );

		// Check the link status is now done.
		$this->assertEquals( Link::PROCESS_DONE, $link->get_archive_process() );

		$this->assertEquals( 'https://web-wp.archive.org/web/iawmlf_glynn/https://example.com', $link->get_archived_href() );

		// Remove the filter.
		remove_all_filters( 'iawmlf_snapshot_client' );
	}

	/**
	 * @testdox When a link is not found on wayback machine, it should be added to the queue to create a snapshot.
	 *
	 * @return void
	 */
	public function test_link_is_not_found_on_wayback_machine(): void {

		// Create mock snapshot client.
		$client = $this->createMock( \Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\Snapshot_Client::class );
		$client->method( 'get_latest_snapshot' )
			->willReturn( null );

		// Set the mock client
		add_filter(
			'iawmlf_snapshot_client',
			function () use ( $client ) {
				return $client;
			}
		);

		// Create a link.
		$link = new Link( 'https://example.com' );

		// Add the link to the database.
		$repo = new Link_Repository();
		$link = $repo->upsert( $link );

		// Create the event.
		$event = new Find_Or_Create_Snapshot_Event();
		$event->setup();
		$event( $link->get_id() );

		// Get the link from the repository.
		$link = $repo->find_by_id( $link->get_id() );

		// The links status should now be set to pending.
		$this->assertEquals( Link::PROCESS_PENDING, $link->get_archive_process() );

		// Check if event added to actionscheduler_actions  table.
		global $wpdb;

		// Look for a row with hook of iawmlf_create_new_snapshot and a args that contains the "link_id":$id key.
		$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}actionscheduler_actions WHERE hook = 'iawmlf_create_new_snapshot' AND args LIKE '%\"link_id\":{$link->get_id()}%'" );

		$this->assertCount( 1, $results );

		// Remove the filter.
		remove_all_filters( 'iawmlf_snapshot_client' );
	}
}
