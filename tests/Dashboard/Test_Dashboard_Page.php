<?php

/**
 * Tests for the Dashboard_Page class.
 *
 * @since 1.5.0
 *
 * @coversDefaultClass \Internet_Archive\Wayback_Machine_Link_Fixer\Dashboard\Dashboard_Page
 *
 * @group Dashboard
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Tests\Dashboard;

use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link;
use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;
use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link_Repository;
use Internet_Archive\Wayback_Machine_Link_Fixer\Dashboard\Dashboard_Page;

/**
 * Test_Dashboard_Page
 */
class Test_Dashboard_Page extends \WP_UnitTestCase {

	/**
	 * Clears the post IDs render_page() caches through Link_Repository::get_post_ids_from_link_id().
	 *
	 * @return void
	 */
	public function tear_down(): void {
		$cache = new \ReflectionProperty( Link_Repository::class, 'link_meta' );
		$cache->setAccessible( true );
		$cache->setValue( null, null );

		parent::tear_down();
	}

	/**
	 * @testdox "Total Broken Links" opens the Links table on the same links it counts, leaving out excluded ones. (#385)
	 *
	 * @return void
	 */
	public function test_total_broken_links_opens_the_links_it_counts(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		// No onboarding, so the link numbers are shown. Nothing asks Archive.org.
		delete_option( Settings::ONBOARDING_DATE_KEY );
		delete_transient( 'iawmlf_dashboard_stats' );
		delete_transient( 'iawmlf_dashboard_onboarding_stats' );
		set_transient( 'iawmlf_account_details', 'NO DATA', HOUR_IN_SECONDS );
		set_transient( 'iawmlf_archive_api_online', 'yes', HOUR_IN_SECONDS );

		$repository = new Link_Repository();
		foreach ( array( 'a', 'b', 'c' ) as $path ) {
			$repository->upsert( ( new Link( 'https://example.com/' . $path ) )->set_broken() );
		}
		$repository->upsert( ( new Link( 'https://example.com/excluded' ) )->set_broken()->set_excluded() );

		ob_start();
		( new Dashboard_Page() )->render_page();
		$html = (string) ob_get_clean();

		$previous = libxml_use_internal_errors( true );
		$document = new \DOMDocument();
		$document->loadHTML( $html );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		$anchor = ( new \DOMXPath( $document ) )->query( '//div[contains(@class, "iawmlf_dashboard-stats-box--danger")]/a' )->item( 0 );

		$this->assertNotNull( $anchor, 'The Total Broken Links number was not rendered.' );
		$this->assertSame( '3', trim( $anchor->textContent ), 'The excluded broken link should not be counted.' );

		parse_str( (string) wp_parse_url( $anchor->getAttribute( 'href' ), PHP_URL_QUERY ), $query );

		$this->assertSame( '1', $query['iawmlf_status'] ?? null );
		$this->assertSame( '0', $query['iawmlf_is_excluded'] ?? null, 'The link should leave out excluded links, as the number does.' );
	}
}
