<?php

/**
 * Tests for Check Validator Status Event.
 *
 * @coversDefaultClass \Internet_Archive\Wayback_Machine_Link_Fixer\Event\Check_Validator_Status
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Tests\Event;

use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link;
use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;
use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link_Repository;
use Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\Snapshot_Client;
use Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\Link_Checker_Client;
use Internet_Archive\Wayback_Machine_Link_Fixer\Event\Check_Validator_Status;

/**
 * Test_Check_Validator_Status
 *
 * @group Event
 * @group Check_Validator_Status
 */
class Test_Check_Validator_Status extends \WP_UnitTestCase {

	private $wpdb;
	private $link_repository;

	/**
	 * Setup
	 */
	public function set_up(): void {
		$this->wpdb            = $GLOBALS['wpdb'];
		$this->link_repository = new Link_Repository();

		$this->wpdb->query( "TRUNCATE TABLE {$this->wpdb->prefix}actionscheduler_actions" );
		remove_all_filters( 'iawmlf_snapshot_client' );
		remove_all_filters( 'iawmlf_link_checker_client' );

		$this->wpdb->query( 'TRUNCATE TABLE ' . Settings::get_link_table_name() );

		parent::set_up();
	}

	/**
	 * Tear down
	 */
	public function tear_down(): void {
		remove_all_filters( 'iawmlf_snapshot_client' );
		remove_all_filters( 'iawmlf_link_checker_client' );

		parent::tear_down();
	}

	/**
	 * Stub the snapshot client to return a given status from get_snapshot_status,
	 * and stub the link checker so no real HTTP fires from is_online().
	 *
	 * @param array $response The response to return from get_snapshot_status.
	 *
	 * @return void
	 */
	private function set_snapshot_client_response( array $response ): void {
		$snapshot = $this->createMock( Snapshot_Client::class );
		$snapshot->method( 'get_snapshot_status' )->willReturn( $response );
		$snapshot->method( 'is_online' )->willReturn( true );
		add_filter( 'iawmlf_snapshot_client', fn() => $snapshot );

		$link_checker = $this->createMock( Link_Checker_Client::class );
		$link_checker->method( 'is_online' )->willReturn( true );
		add_filter( 'iawmlf_link_checker_client', fn() => $link_checker );
	}

	/**
	 * @testdox A not-excluded link going through a success status is a no-op for exclusion state.
	 *
	 * @return void
	 */
	public function test_success_status_no_op_when_not_excluded(): void {
		$this->set_snapshot_client_response( array( 'status' => 'success' ) );

		$link = $this->link_repository->upsert( new Link( 'https://example.com' ) );

		$event = new Check_Validator_Status();
		$event->setup();

		$event( $link->get_id(), 'fake-job', 0 );

		$updated = $this->link_repository->find_by_id( $link->get_id() );

		$this->assertFalse( $updated->is_excluded() );
	}

	/**
	 * @testdox A system-excluded link (no-access) has its exclusion lifted and stale message cleared when the validator job comes back success.
	 *
	 * @return void
	 */
	public function test_success_status_lifts_system_exclusion_and_clears_message(): void {
		$this->set_snapshot_client_response( array( 'status' => 'success' ) );

		$link = $this->link_repository->upsert( new Link( 'https://example.com' ) );
		$link->set_excluded();
		$link->set_message( 'error:no-access' );
		$this->link_repository->upsert( $link );

		$event = new Check_Validator_Status();
		$event->setup();

		$event( $link->get_id(), 'fake-job', 0 );

		$updated = $this->link_repository->find_by_id( $link->get_id() );

		$this->assertFalse( $updated->is_excluded(), 'System-set exclusion should be lifted on validator success.' );
		$this->assertSame( '', $updated->get_message(), 'Stale system error message should be cleared when the exclusion is lifted.' );
	}

	/**
	 * @testdox A manually excluded link keeps its exclusion and message even when the validator job comes back success.
	 *
	 * @return void
	 */
	public function test_success_status_preserves_manual_exclusion(): void {
		$manual_message = 'User Requested To Exclude (admin on 28 May 2026)';

		$this->set_snapshot_client_response( array( 'status' => 'success' ) );

		$link = $this->link_repository->upsert( new Link( 'https://example.com' ) );
		$link->set_excluded();
		$link->set_message( $manual_message );
		$this->link_repository->upsert( $link );

		$event = new Check_Validator_Status();
		$event->setup();

		$event( $link->get_id(), 'fake-job', 0 );

		$updated = $this->link_repository->find_by_id( $link->get_id() );

		$this->assertTrue( $updated->is_excluded(), 'Manual exclusion must survive a successful validator.' );
		$this->assertSame( $manual_message, $updated->get_message(), 'Manual exclusion message must not be touched.' );
	}
}
