<?php

/**
 * Tests for Link_Latest_Snapshot_Action
 *
 * @coversDefaultClass \Internet_Archive\Wayback_Machine_Link_Fixer\Action\Link_Latest_Snapshot_Action
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Tests\Action;

use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link;
use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;
use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link_Repository;
use Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\Snapshot_Client;
use Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\Link_Checker_Client;
use Internet_Archive\Wayback_Machine_Link_Fixer\Action\Link_Latest_Snapshot_Action;

/**
 * Test_Link_Latest_Snapshot_Action
 *
 * @group Action
 * @group Link_Latest_Snapshot_Action
 */
class Test_Link_Latest_Snapshot_Action extends \WP_UnitTestCase {

	private $wpdb;
	private $link_repository;

	/**
	 * Setup
	 */
	public function set_up(): void {
		$this->wpdb            = $GLOBALS['wpdb'];
		$this->link_repository = new Link_Repository();

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
	 * Stub the snapshot client to return a given archive URL from get_latest_snapshot,
	 * and stub the link checker so no real HTTP calls fire from is_online().
	 *
	 * @param string|null $archive_url The archive URL to return, or null to simulate no archive.
	 *
	 * @return void
	 */
	private function stub_snapshot_returning( ?string $archive_url ): void {
		$snapshot = $this->createMock( Snapshot_Client::class );
		$snapshot->method( 'get_latest_snapshot' )->willReturn(
			null === $archive_url ? null : array( 'url' => $archive_url )
		);
		$snapshot->method( 'is_online' )->willReturn( true );
		add_filter( 'iawmlf_snapshot_client', fn() => $snapshot );

		$link_checker = $this->createMock( Link_Checker_Client::class );
		$link_checker->method( 'is_online' )->willReturn( true );
		add_filter( 'iawmlf_link_checker_client', fn() => $link_checker );
	}

	/**
	 * @testdox When the link is not excluded, rescan_link updates the archived URL and exclusion stays false.
	 *
	 * @return void
	 */
	public function test_not_excluded_link_gets_archive_updated(): void {
		$archive_url = 'https://archive.com/web/2024/https://example.com';
		$this->stub_snapshot_returning( $archive_url );

		$link = $this->link_repository->upsert( new Link( 'https://example.com' ) );

		$action = new Link_Latest_Snapshot_Action();
		$result = $action->rescan_link( $link->get_id() );

		$updated = $this->link_repository->find_by_id( $link->get_id() );

		$this->assertTrue( $result['updated'] );
		$this->assertSame( $archive_url, $updated->get_archived_href() );
		$this->assertFalse( $updated->is_excluded() );
	}

	/**
	 * @testdox A manually excluded link keeps its exclusion and message even when rescan_link succeeds.
	 *
	 * @return void
	 */
	public function test_manual_exclusion_is_preserved(): void {
		$archive_url      = 'https://archive.com/web/2024/https://example.com';
		$manual_message   = 'User Requested To Exclude (admin on 28 May 2026)';

		$this->stub_snapshot_returning( $archive_url );

		$link = new Link( 'https://example.com' );
		$link->set_excluded();
		$link->set_message( $manual_message );
		$link = $this->link_repository->upsert( $link );

		$action = new Link_Latest_Snapshot_Action();
		$result = $action->rescan_link( $link->get_id() );

		$updated = $this->link_repository->find_by_id( $link->get_id() );

		$this->assertTrue( $result['updated'] );
		$this->assertSame( $archive_url, $updated->get_archived_href() );
		$this->assertTrue( $updated->is_excluded(), 'Manual exclusion must survive a successful rescan.' );
		$this->assertSame( $manual_message, $updated->get_message(), 'Manual exclusion message must not be touched.' );
	}

	/**
	 * @testdox A system-excluded link (no-access) has its exclusion lifted and stale message cleared when rescan_link finds an archive.
	 *
	 * @return void
	 */
	public function test_system_exclusion_is_lifted_and_message_cleared(): void {
		$archive_url = 'https://archive.com/web/2024/https://example.com';
		$this->stub_snapshot_returning( $archive_url );

		$link = new Link( 'https://example.com' );
		$link->set_excluded();
		$link->set_message( 'error:no-access' );
		$link = $this->link_repository->upsert( $link );

		$action = new Link_Latest_Snapshot_Action();
		$result = $action->rescan_link( $link->get_id() );

		$updated = $this->link_repository->find_by_id( $link->get_id() );

		$this->assertTrue( $result['updated'] );
		$this->assertSame( $archive_url, $updated->get_archived_href() );
		$this->assertFalse( $updated->is_excluded(), 'System-set exclusion should be lifted on successful rescan.' );
		$this->assertSame( '', $updated->get_message(), 'Stale system error message should be cleared when the exclusion is lifted.' );
	}
}
