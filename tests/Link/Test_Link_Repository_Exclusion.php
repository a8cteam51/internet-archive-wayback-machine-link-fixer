<?php

/**
 * Tests that retrieval honours the exclusion list: a link matched by a list rule is skipped even
 * when its own per-link excluded flag is false.
 *
 * @coversDefaultClass \Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link_Repository
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Tests\Link;

use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link;
use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;
use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link_Repository;

/**
 * Test_Link_Repository_Exclusion
 *
 * @group Link
 * @group Link_Exclusion
 */
class Test_Link_Repository_Exclusion extends \WP_UnitTestCase {

	private $wpdb;
	private $link_repository;

	public function set_up(): void {
		$this->wpdb            = $GLOBALS['wpdb'];
		$this->link_repository = new Link_Repository();

		$this->wpdb->query( 'TRUNCATE TABLE ' . Settings::get_link_table_name() );
		delete_option( Settings::LINK_EXCLUSIONS );
		remove_all_filters( 'iawmlf_bundled_link_exclusions' );

		parent::set_up();
	}

	public function tear_down(): void {
		delete_option( Settings::LINK_EXCLUSIONS );
		remove_all_filters( 'iawmlf_bundled_link_exclusions' );

		parent::tear_down();
	}

	/**
	 * @testdox A link whose own excluded flag is false but which matches a list rule is still excluded from retrieval.
	 *
	 * @return void
	 */
	public function test_list_matched_link_is_excluded_even_when_flag_is_false(): void {
		$post_id = self::factory()->post->create();

		// Not flagged on the link itself (simulates a user who un-checked "Exclude this link").
		$link = $this->link_repository->upsert( new Link( 'https://blocked.example/x' ) );
		$this->assertFalse( $link->is_excluded() );

		update_post_meta( $post_id, Settings::LINK_META_KEY, array( $link->get_id() ) );

		// The link matches a built-in list rule.
		add_filter( 'iawmlf_bundled_link_exclusions', fn(): array => array( '*blocked.example*' ) );

		// With exclusion filtering on, the list wins: the link is skipped.
		$filtered = $this->link_repository->get_links_for_post( $post_id, true );
		$this->assertTrue( $filtered->is_empty(), 'A list-matched link must be excluded from retrieval regardless of its own flag.' );

		// With filtering off, the link is still returned.
		$unfiltered = $this->link_repository->get_links_for_post( $post_id, false );
		$this->assertCount( 1, $unfiltered->get_links() );
	}
}
