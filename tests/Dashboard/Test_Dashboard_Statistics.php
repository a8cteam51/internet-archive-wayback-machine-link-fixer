<?php

/**
 * Tests for the Dashboard_Statistics class.
 *
 * @since 1.5.0
 *
 * @coversDefaultClass \Internet_Archive\Wayback_Machine_Link_Fixer\Dashboard\Dashboard_Statistics
 *
 * @group Dashboard
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Tests\Dashboard;

use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;
use Internet_Archive\Wayback_Machine_Link_Fixer\Dashboard\Dashboard_Statistics;

/**
 * Test_Dashboard_Statistics
 */
class Test_Dashboard_Statistics extends \WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();

		// Only count the posts each test creates.
		$existing = get_posts(
			array(
				'post_type'      => 'any',
				'post_status'    => 'any',
				'posts_per_page' => -1,
			)
		);
		foreach ( $existing as $post ) {
			wp_delete_post( $post->ID, true );
		}

		// The bootstrap turns link processing on, which would scan each published post as it is created.
		update_option( Settings::PROCESS_LINKS, false );

		// Onboarding started yesterday, so it is still running.
		update_option( Settings::ONBOARDING_DATE_KEY, gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS ) );
		delete_transient( 'iawmlf_dashboard_onboarding_stats' );

		// The Link Fixer Dashboard page, viewed by an administrator.
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		set_current_screen( 'dashboard' );
	}

	/**
	 * Creates a post, marked as scanned or not.
	 *
	 * @param string  $status  The post status.
	 * @param boolean $scanned Whether the post has been scanned.
	 *
	 * @return integer The post ID.
	 */
	private function create_post( string $status, bool $scanned ): int {
		$post_id = self::factory()->post->create( array( 'post_status' => $status ) );

		if ( $scanned ) {
			update_post_meta( $post_id, Settings::LINK_META_KEY, array() );
		}

		return $post_id;
	}

	/**
	 * @testdox Onboarding only counts published posts that are not excluded, as those are the only ones the scan reaches. (#385)
	 *
	 * @return void
	 */
	public function test_onboarding_counts_only_posts_the_scan_can_reach(): void {
		$this->create_post( 'publish', true );
		$this->create_post( 'publish', false );
		$this->create_post( 'draft', false );
		$this->create_post( 'private', false );
		$excluded_id = $this->create_post( 'publish', false );

		update_option( Settings::LINK_FIXER_EXCLUDED_POSTS, array( $excluded_id ) );

		$stats = Dashboard_Statistics::get_onboarding_statistics();

		$this->assertTrue( $stats['show_onboarding'] );
		$this->assertSame( 2, $stats['total_post_count'] );
		$this->assertSame( 1, $stats['unprocessed_post_count'] );
	}

	/**
	 * @testdox Onboarding ends once every published, non-excluded post is scanned, even with drafts and excluded posts left. (#385)
	 *
	 * @return void
	 */
	public function test_onboarding_ends_once_every_reachable_post_is_scanned(): void {
		$this->create_post( 'publish', true );
		$this->create_post( 'draft', false );
		$excluded_id = $this->create_post( 'publish', false );

		update_option( Settings::LINK_FIXER_EXCLUDED_POSTS, array( $excluded_id ) );

		$this->assertFalse( Dashboard_Statistics::get_onboarding_statistics()['show_onboarding'] );
	}
}
