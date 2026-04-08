<?php

/**
 * Tests for the WP_Post_Table_Controller class.
 *
 * @since 1.4.0
 *
 * @coversDefaultClass \Internet_Archive\Wayback_Machine_Link_Fixer\WP_Post\WP_Post_Table_Controller
 */
declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Tests\WP_Post;

use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link;
use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link_Repository;
use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;
use Internet_Archive\Wayback_Machine_Link_Fixer\Dashboard\Settings_Page;
use Internet_Archive\Wayback_Machine_Link_Fixer\WP_Post\WP_Post_Table_Controller;

/**
 * Test_WP_Post_Table_Controller
 */
class Test_WP_Post_Table_Controller extends \WP_UnitTestCase {

	/**
	 * @testdox It should show 1 broken out of 2 when a post has 1 broken and 1 valid link.
	 *
	 * @return void
	 */
	public function test_render_link_column_shows_broken_count(): void {
		$post_id         = self::factory()->post->create();
		$link_repository = new Link_Repository();

		// Create a broken link.
		$broken_link = new Link( 'https://example.com/broken' );
		$broken_link->set_broken();
		$broken_link = $link_repository->upsert( $broken_link );

		// Create a valid link.
		$valid_link = new Link( 'https://example.com/valid' );
		$valid_link = $link_repository->upsert( $valid_link );

		// Set the link IDs in post meta.
		update_post_meta( $post_id, Settings::LINK_META_KEY, array( $broken_link->get_id(), $valid_link->get_id() ) );

		$controller = new WP_Post_Table_Controller();

		ob_start();
		$controller->render_link_column( WP_Post_Table_Controller::LINK_COLUMN_KEY, $post_id );
		$output = ob_get_clean();

		$this->assertStringContainsString( '<strong>1</strong> broken out of <strong>2</strong>', $output );
	}

	/**
	 * @testdox It should show "Excluded post" with a link to settings when the post is excluded.
	 *
	 * @return void
	 */
	public function test_render_link_column_shows_excluded_post(): void {
		$post_id         = self::factory()->post->create();
		$link_repository = new Link_Repository();

		// Create a link and attach to the post.
		$link = new Link( 'https://example.com/page' );
		$link = $link_repository->upsert( $link );
		update_post_meta( $post_id, Settings::LINK_META_KEY, array( $link->get_id() ) );

		// Exclude this post.
		update_option( Settings::LINK_FIXER_EXCLUDED_POSTS, array( $post_id ) );

		$controller = new WP_Post_Table_Controller();

		ob_start();
		$controller->render_link_column( WP_Post_Table_Controller::LINK_COLUMN_KEY, $post_id );
		$output = ob_get_clean();

		$this->assertStringContainsString( '<em>', $output, 'Should wrap in em tags.' );
		$this->assertStringContainsString( 'Excluded post', $output, 'Should show excluded post text.' );
		$this->assertStringContainsString( Settings_Page::get_page_url(), $output, 'Should link to settings page.' );

		// Clean up.
		delete_option( Settings::LINK_FIXER_EXCLUDED_POSTS );
	}
}
