<?php

/**
 * Tests for the Wayback Machine Service.
 *
 * @coversDefaultClass \Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\Wayback_Machine_Service
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer_Tests\Wayback_Machine;

use Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\Wayback_Machine_Service;
use Internet_Archive\Wayback_Machine_Link_Fixer_Tests\Tools\Wayback_Machine_Helper;

/**
 * Test_Wayback_Machine_Service
 *
 * @group Wayback_Machine
 * @group Wayback_Machine_Service
 */
class Test_Wayback_Machine_Service extends \WP_UnitTestCase {

	use Wayback_Machine_Helper;

	/**
	 * Setup
	 */
	public function set_up(): void {
		$this->clear_clients();
		parent::set_up();
	}

	/**
	 * Tear down
	 */
	public function tear_down(): void {
		$this->clear_clients();
		parent::tear_down();
	}

	/**
	 * @testdox is_online() returns true only when both clients are online.
	 *
	 * @return void
	 */
	public function test_is_online_true_when_both_clients_online(): void {
		$this->create_service( true );

		$this->assertTrue( ( new Wayback_Machine_Service() )->is_online() );
	}

	/**
	 * @testdox is_online() returns false when both clients are offline.
	 *
	 * @return void
	 */
	public function test_is_online_false_when_both_clients_offline(): void {
		$this->create_service( false );

		$this->assertFalse( ( new Wayback_Machine_Service() )->is_online() );
	}

	/**
	 * @testdox is_online() returns false when only the snapshot client is down.
	 *
	 * @return void
	 */
	public function test_is_online_false_when_snapshot_client_offline(): void {
		$this->create_service(
			true,
			function ( array $clients ) {
				$offline = $this->createMock( \Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\Snapshot_Client::class );
				$offline->method( 'is_online' )->willReturn( false );
				$clients['snapshot'] = $offline;
				return $clients;
			}
		);

		$this->assertFalse( ( new Wayback_Machine_Service() )->is_online() );
	}

	/**
	 * @testdox is_online() returns false when only the link checker client is down.
	 *
	 * @return void
	 */
	public function test_is_online_false_when_link_checker_client_offline(): void {
		$this->create_service(
			true,
			function ( array $clients ) {
				$offline = $this->createMock( \Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\Link_Checker_Client::class );
				$offline->method( 'is_online' )->willReturn( false );
				$clients['link_checker'] = $offline;
				return $clients;
			}
		);

		$this->assertFalse( ( new Wayback_Machine_Service() )->is_online() );
	}
}
