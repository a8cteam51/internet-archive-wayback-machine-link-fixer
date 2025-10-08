<?php

/**
 * Tests for the bulk action on the report table
 *
 * @since 1.3.0
 *
 * @coversDefaultClass WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report_Table
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Tests\Report;

use Gin0115\WPUnit_Helpers\Objects;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report_Table;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link_Repository;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Snapshot_Client;

/**
 * Test_Report_Table_Actions
 *
 * @group Action
 * @group Test_Report_Table_Actions
 */
class Test_Report_Table_Actions extends \WP_UnitTestCase {

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
	 * Get a mocked up Table instance.
	 *
	 * @return Report_Table
	 */
	private function get_mocked_table(): Report_Table {
		return new class($this->link_repository) extends Report_Table {

			public function test_links( array $links ): void {
				Objects::invoke_method( $this, 'process_new_snapshot', array( $links ) );
			}

			public function get_notices(): ?array {
				return $this->notices;
			}
		};
	}


	/**
	 * @testdox When creating a new snapshot for less than 10 links, just process and start the process of creating a new snapshot.
	 *
	 * @return void
	 */
	public function test_create_new_snapshot_for_less_than_10_links(): void {
		// Create 4 links.
		$all = array(
			new Link( 'https://glynnquelch.co.uk/link' ),
			new Link( 'https://glynnquelch.co.uk/link2' ),
			new Link( 'https://glynnquelch.co.uk/link3' ),
			new Link( 'https://glynnquelch.co.uk/link4' ),
		);

		$calls = 0;

		// Use a mock IA insstance
		$client = $this->createMock( \WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Snapshot_Client::class );
		$client->method( 'create_snapshot' )
			->willReturnCallback(function($link) use( &$calls) {
				$calls++;
				return 'some-id';
			});

		// Set the mock client
		add_filter(
			'iawmlf_snapshot_client',
			function () use ( $client ) {
				return $client;
			}
		);

		// Iterate through the links and save them.
		foreach ( $all as $link ) {
			$this->link_repository->upsert( $link );
		}

		// Create the action
		$table = $this->get_mocked_table();

		$links    = $this->link_repository->query_links( 9999 );
		$link_ids = array_map( fn( Link $link ) => $link->get_id(), $links );
		$table->test_links( $link_ids );
		$notices = $table->get_notices();

		$this->assertCount(4, $notices );
		$this->assertCount(4, array_filter(
			$notices,
			fn( $notice ) => $notice['type'] === 'success'
		) );
		$this->assertCount(0, array_filter(
			$notices,
			fn( $notice ) => $notice['type'] === 'error'
		) );
		$this->assertEquals(4, $calls);

		// Remove the filter.
		remove_all_filters( 'iawmlf_snapshot_client' );


	}

	/**
	 * @testdox When trying to create snapshots for more than 10 links, it should create a new action to process the links in batches.
	 *
	 * @return void
	 */
	public function test_create_new_snapshot_for_more_than_10_links(): void {
		// Create 12 links.
		$all = array(
			new Link( 'https://glynnquelch.co.uk/link' ),
			new Link( 'https://glynnquelch.co.uk/link2' ),
			new Link( 'https://glynnquelch.co.uk/link3' ),
			new Link( 'https://glynnquelch.co.uk/link4' ),
			new Link( 'https://glynnquelch.co.uk/link5' ),
			new Link( 'https://glynnquelch.co.uk/link6' ),
			new Link( 'https://glynnquelch.co.uk/link7' ),
			new Link( 'https://glynnquelch.co.uk/link8' ),
			new Link( 'https://glynnquelch.co.uk/link9' ),
			new Link( 'https://glynnquelch.co.uk/link10' ),
			new Link( 'https://glynnquelch.co.uk/link11' ),
			new Link( 'https://glynnquelch.co.uk/link12' ),
		);

		// Use a mock IA insstance
		$client = $this->createMock( \WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Snapshot_Client::class );
		$client->method( 'create_snapshot' )
			->willReturn('some-id');

		// Set the mock client
		add_filter(
			'iawmlf_snapshot_client',
			function () use ( $client ) {
				return $client;
			}
		);

		foreach ( $all as $link ) {
			$this->link_repository->upsert( $link );
		}

		// Create the action
		$table = $this->get_mocked_table();

		$links    = $this->link_repository->query_links( 9999 );
		$link_ids = array_map( fn( Link $link ) => $link->get_id(), $links );

		$table->test_links( $link_ids );
		$notices = $table->get_notices();

		// Remove the filter.
		remove_all_filters( 'iawmlf_snapshot_client' );

		// We should have only 1 notice.
		$this->assertCount( 1, $notices );
		$this->assertEquals( 'success', $notices[0]['type'] );

		// The message should contain all the links hrefs.
		foreach( $all as $link ) {
			$this->assertStringContainsString( $link->get_href(), $notices[0]['message'] );
		}

		// Check how many actions are scheduled.
		$actions = $this->wpdb->get_results( "SELECT args FROM {$this->wpdb->prefix}actionscheduler_actions where hook='iawmlf_create_new_snapshot'" );
		$this->assertCount( 12, $actions );

		// Iterate over the actions and check the id in args is id in array.
		foreach ( $actions as $action ) {
			$args = json_decode( $action->args, true );
			$this->assertContains( $args['link_id'], $link_ids );
		}
	}

	/**
	 * @testdox when we send a chunk of urls to have new snapshots created, own site and IA archive links should be omitted and reported back.
	 *
	 * @return void
	 */
	public function test_create_new_snapshot_omits_own_and_ia_links(): void {
		// Create 12
		$valid = array(
			new Link( 'https://glynnquelch.co.uk/link' ),
			new Link( 'https://glynnquelch.co.uk/link2' ),
			new Link( 'https://glynnquelch.co.uk/link3' ),
			new Link( 'https://glynnquelch.co.uk/link4' ),
		);

		$invalid = [
			// Own site links.
			new Link( 'https://example.org/link' ),
			new Link( 'https://example.org/link2' ),
			new Link( 'https://example.org/link3' ),
			new Link( 'https://example.org/link4' ),
			// IA links.
			new Link( 'https://web.archive.org/web/link' ),
			new Link( 'https://web.archive.org/web/link2' ),
			new Link( 'https://web.archive.org/web/link3' ),
			new Link( 'https://web.archive.org/web/link4' ),
		];


		foreach ( array_merge($valid, $invalid) as $link ) {
			$this->link_repository->upsert( $link );
		}

		// Create the action
		$table = $this->get_mocked_table();

		$links    = $this->link_repository->query_links( 9999 );
		$link_ids = array_map( fn( Link $link ) => $link->get_id(), $links );

		$table->test_links( $link_ids );
		$notices = $table->get_notices();

		// We should have both success and error notices.
		$this->assertCount( 2, $notices );

		$success_notices = array_filter(
			$notices,
			fn( $notice ) => $notice['type'] === 'success'
		);
		$error_notices = array_filter(
			$notices,
			fn( $notice ) => $notice['type'] === 'error'
		);

		// Iterating over the valid urls, they should be in the success notice.
		foreach ( $valid as $link ) {
			$this->assertStringContainsString( $link->get_href(), \array_values($success_notices)[0]['message'] );
		}
		// Iterating over the invalid urls, they should be in the error notice.
		foreach ( $invalid as $link ) {
			$this->assertStringContainsString( $link->get_href(),\array_values( $error_notices)[0]['message'] );
		}

		// Check how many actions are scheduled.
		$actions = $this->wpdb->get_results( "SELECT args FROM {$this->wpdb->prefix}actionscheduler_actions where hook='iawmlf_create_new_snapshot'" );
		$this->assertCount( 4, $actions );
	}
}
