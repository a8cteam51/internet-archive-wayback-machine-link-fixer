<?php

/**
 * Testthe Find or Create Snapshot Event
 *
 * @since 1.2.0
 *
 * @coversDefaultClass \WPCOMSpecialProjects\Wayback_Link_Fixer\Event\Find_Or_Create_Snapshot_Event
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Tests\Event;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link_Repository;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Event\Find_Or_Create_Snapshot_Event;

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


	/** */

	/**
	 * @testdox When a link is found on wayback machine, it should have its snapshot url added and not added to the queue to create.
	 *
	 * @return void
	 */
	public function test_link_is_found_on_wayback_machine(): void {

		// Create mock snapshot client.
		$client = $this->createMock( \WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Snapshot_Client::class );
		$client->method( 'get_latest_snapshot' )
			->willReturn( array( 'url' => 'https://web.archive.org/web/wlf_glynn/https://example.com' ) );

		// Set the mock client
		add_filter(
			'wlf_snapshot_client',
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

		$this->assertEquals( 'https://web.archive.org/web/wlf_glynn/https://example.com', $link->get_archived_href() );

		// Remove the filter.
		remove_all_filters( 'wlf_snapshot_client' );
	}

	/**
	 * @testdox When a link is not found on wayback machine, it should be added to the queue to create a snapshot.
	 *
	 * @return void
	 */
	public function test_link_is_not_found_on_wayback_machine(): void {

		// Create mock snapshot client.
		$client = $this->createMock( \WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Snapshot_Client::class );
		$client->method( 'get_latest_snapshot' )
			->willReturn( null );

		// Set the mock client
		add_filter(
			'wlf_snapshot_client',
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

		// Check if event added to actionscheduler_actions  table.
		global $wpdb;

		// Look for a row with hook of wlf_create_new_snapshot and a args that contains the "link_id":$id key.
		$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}actionscheduler_actions WHERE hook = 'wlf_create_new_snapshot' AND args LIKE '%\"link_id\":{$link->get_id()}%'" );

		$this->assertCount( 1, $results );

		// Remove the filter.
		remove_all_filters( 'wlf_snapshot_client' );
	}
}
