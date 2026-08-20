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

use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;
use Internet_Archive\Wayback_Machine_Link_Fixer\Dashboard\Settings_Page;
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

	/**
	 * @testdox Cached account details must be normalised on read, so partial payloads render safely.
	 *
	 * @return void
	 */
	public function test_cached_account_details_are_normalised_on_read(): void {
		// A raw API payload, cached, missing most of the expected keys.
		set_transient( 'iawmlf_account_details', array( 'available' => '5' ), HOUR_IN_SECONDS );

		$this->assertSame(
			array(
				'available'            => 5,
				'daily_captures'       => 0,
				'daily_captures_limit' => 0,
				'processing'           => 0,
			),
			Dashboard_Notifications::get_account_details()
		);
	}

	/**
	 * @testdox Saving either archive.org key must clear the cached account details.
	 *
	 * @return void
	 */
	public function test_saving_keys_clears_cached_account_details(): void {
		( new Settings_Page() )->initialize();

		// First-time save (add_option path).
		set_transient( 'iawmlf_account_details', 'NO DATA', HOUR_IN_SECONDS );
		update_option( Settings::ARCHIVE_ORG_ACCESS_KEY, 'first-access-key' );
		$this->assertFalse( get_transient( 'iawmlf_account_details' ), 'A first-time access key save must clear the cached account details.' );

		// Re-save (update_option path).
		set_transient( 'iawmlf_account_details', 'NO DATA', HOUR_IN_SECONDS );
		update_option( Settings::ARCHIVE_ORG_ACCESS_KEY, 'corrected-access-key' );
		$this->assertFalse( get_transient( 'iawmlf_account_details' ), 'An access key change must clear the cached account details.' );

		// The secret key clears it too.
		set_transient( 'iawmlf_account_details', 'NO DATA', HOUR_IN_SECONDS );
		update_option( Settings::ARCHIVE_ORG_SECRET_KEY, 'first-secret-key' );
		$this->assertFalse( get_transient( 'iawmlf_account_details' ), 'A secret key save must clear the cached account details.' );
	}
}
