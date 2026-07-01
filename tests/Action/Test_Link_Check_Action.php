<?php

/**
 * Tests that Link_Check_Action skips a link matched by the exclusion list instead of checking it.
 *
 * @coversDefaultClass \Internet_Archive\Wayback_Machine_Link_Fixer\Action\Link_Check_Action
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Tests\Action;

use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link;
use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;
use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link_Repository;
use Internet_Archive\Wayback_Machine_Link_Fixer\Action\Link_Check_Action;
use Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\Link_Checker_Client;

/**
 * Test_Link_Check_Action
 *
 * @group Action
 * @group Link_Exclusion
 */
class Test_Link_Check_Action extends \WP_UnitTestCase {

	private $wpdb;
	private $link_repository;

	public function set_up(): void {
		$this->wpdb            = $GLOBALS['wpdb'];
		$this->link_repository = new Link_Repository();

		$this->wpdb->query( 'TRUNCATE TABLE ' . Settings::get_link_table_name() );
		delete_option( Settings::LINK_EXCLUSIONS );
		remove_all_filters( 'iawmlf_link_checker_client' );
		remove_all_filters( 'iawmlf_bundled_link_exclusions' );

		parent::set_up();
	}

	public function tear_down(): void {
		delete_option( Settings::LINK_EXCLUSIONS );
		remove_all_filters( 'iawmlf_link_checker_client' );
		remove_all_filters( 'iawmlf_bundled_link_exclusions' );

		parent::tear_down();
	}

	/**
	 * @testdox A link matched by the exclusion list is treated as valid without ever calling the checker.
	 *
	 * @return void
	 */
	public function test_list_excluded_link_does_not_call_checker(): void {
		add_filter( 'iawmlf_bundled_link_exclusions', fn(): array => array( '*blocked.example*' ) );

		$link = $this->link_repository->upsert( new Link( 'https://blocked.example/x' ) );

		$checker = $this->createMock( Link_Checker_Client::class );
		$checker->method( 'is_online' )->willReturn( true );
		$checker->expects( $this->never() )->method( 'check_single' );
		add_filter( 'iawmlf_link_checker_client', fn() => $checker );

		$action = new Link_Check_Action();
		$result = $action->check_link( $link->get_id() );

		$this->assertFalse( $result['checked'], 'A list-excluded link should not be checked.' );
		$this->assertTrue( $result['valid'], 'A list-excluded link is treated as valid.' );
	}

	/**
	 * @testdox A link that matches no exclusion list is checked as normal.
	 *
	 * @return void
	 */
	public function test_non_excluded_link_is_checked(): void {
		$link = $this->link_repository->upsert( new Link( 'https://allowed.example/x' ) );

		$checker = $this->createMock( Link_Checker_Client::class );
		$checker->method( 'is_online' )->willReturn( true );
		$checker->expects( $this->once() )->method( 'check_single' )->willReturn( 200 );
		add_filter( 'iawmlf_link_checker_client', fn() => $checker );

		$action = new Link_Check_Action();
		$result = $action->check_link( $link->get_id() );

		$this->assertTrue( $result['checked'] );
	}
}
