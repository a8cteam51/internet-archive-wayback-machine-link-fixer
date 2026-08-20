<?php

/**
 * Tests for the Dashboard Notifications.
 *
 * @since 1.4.4
 *
 * @coversDefaultClass \Internet_Archive\Wayback_Machine_Link_Fixer\Dashboard\Dashboard_Notifications
 *
 * @group Dashboard
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Tests\Dashboard;

use Internet_Archive\Wayback_Machine_Link_Fixer\Dashboard\Dashboard_Notifications;
use Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\System_Client;

/**
 * Test_Dashboard_Notifications
 */
class Test_Dashboard_Notifications extends \WP_UnitTestCase {

	/**
	 * On tear down, remove the filters and transients.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();
		remove_all_filters( 'iawmlf_system_client' );
		delete_transient( 'iawmlf_account_details' );
	}

	/**
	 * @testdox A failed account details lookup must be cached, not retried on every call. (S064)
	 *
	 * @return void
	 */
	public function test_failed_account_details_lookup_is_cached(): void {
		delete_transient( 'iawmlf_account_details' );

		$client = $this->createMock( System_Client::class );
		$client->expects( $this->once() )
			->method( 'get_user_stats' )
			->willReturn( null );

		add_filter( 'iawmlf_system_client', fn() => $client );

		$this->assertNull( Dashboard_Notifications::get_account_details() );

		// The second call must be served from the cached failure - the client must not be asked again.
		$this->assertNull( Dashboard_Notifications::get_account_details() );
	}
}
