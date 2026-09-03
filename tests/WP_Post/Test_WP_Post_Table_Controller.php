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

	/**
	 * @testdox It should show "No links found" when the post has no link meta at all.
	 *
	 * @return void
	 */
	public function test_render_link_column_shows_no_links_found_without_meta(): void {
		$post_id = self::factory()->post->create();

		$controller = new WP_Post_Table_Controller();

		ob_start();
		$controller->render_link_column( WP_Post_Table_Controller::LINK_COLUMN_KEY, $post_id );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'No links found', $output );
	}

	/**
	 * @testdox It should show "No links found" when the link meta holds only ids that no longer resolve.
	 *
	 * @return void
	 */
	public function test_render_link_column_shows_no_links_found_for_unresolvable_ids(): void {
		$post_id = self::factory()->post->create();

		update_post_meta( $post_id, Settings::LINK_META_KEY, array( 999999 ) );

		$controller = new WP_Post_Table_Controller();

		ob_start();
		$controller->render_link_column( WP_Post_Table_Controller::LINK_COLUMN_KEY, $post_id );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'No links found', $output );
	}

	/**
	 * Renders a column and returns its markup.
	 *
	 * @param string  $column_name The column to render.
	 * @param integer $post_id     The post ID.
	 *
	 * @return string
	 */
	private function render_archived_column( string $column_name, int $post_id ): string {
		$controller = new WP_Post_Table_Controller();

		ob_start();
		$controller->render_archived_column( $column_name, $post_id );
		return (string) ob_get_clean();
	}

	/**
	 * @testdox It should show the date a post was last archived, using the site's date and time format. (#351)
	 *
	 * @return void
	 */
	public function test_render_archived_column_shows_the_archive_date(): void {
		$post_id     = self::factory()->post->create();
		$archived_at = 1735689600; // 2025-01-01 00:00:00 UTC.

		update_post_meta( $post_id, Settings::OWN_LINK_LAST_PROCESSED, $archived_at );

		$output = $this->render_archived_column( WP_Post_Table_Controller::ARCHIVED_COLUMN_KEY, $post_id );

		$expected = wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $archived_at );

		$this->assertStringContainsString( $expected, $output, 'Should show the date in the site format.' );
		$this->assertStringContainsString( gmdate( 'c', $archived_at ), $output, 'Should carry a machine readable datetime.' );
	}

	/**
	 * @testdox It should show "Never archived" when the post has no archive date. (#351)
	 *
	 * @return void
	 */
	public function test_render_archived_column_shows_never_archived_without_meta(): void {
		$post_id = self::factory()->post->create();

		$output = $this->render_archived_column( WP_Post_Table_Controller::ARCHIVED_COLUMN_KEY, $post_id );

		$this->assertStringContainsString( 'Never archived', $output );
		$this->assertStringNotContainsString( '<time', $output, 'Should not render a time element without a date.' );
	}

	/**
	 * @testdox It should show "Never archived" when the stored archive date is empty or zero. (#351)
	 *
	 * @return void
	 */
	public function test_render_archived_column_shows_never_archived_for_a_zero_date(): void {
		$post_id = self::factory()->post->create();

		update_post_meta( $post_id, Settings::OWN_LINK_LAST_PROCESSED, 0 );

		$output = $this->render_archived_column( WP_Post_Table_Controller::ARCHIVED_COLUMN_KEY, $post_id );

		$this->assertStringContainsString( 'Never archived', $output );
	}

	/**
	 * @testdox It should show "Excluded post" with a link to settings when the post is excluded from auto archiving. (#351)
	 *
	 * @return void
	 */
	public function test_render_archived_column_shows_excluded_post(): void {
		$post_id = self::factory()->post->create();

		update_post_meta( $post_id, Settings::OWN_LINK_LAST_PROCESSED, time() );
		update_option( Settings::AUTO_ARCHIVER_EXCLUDED_POSTS, array( $post_id ) );

		$output = $this->render_archived_column( WP_Post_Table_Controller::ARCHIVED_COLUMN_KEY, $post_id );

		$this->assertStringContainsString( 'Excluded post', $output );
		$this->assertStringContainsString( Settings_Page::get_page_url(), $output, 'Should link to settings page.' );
		$this->assertStringNotContainsString( '<time', $output, 'An excluded post should not show a date.' );

		delete_option( Settings::AUTO_ARCHIVER_EXCLUDED_POSTS );
	}

	/**
	 * @testdox It should render nothing when asked for a different column. (#351)
	 *
	 * @return void
	 */
	public function test_render_archived_column_ignores_other_columns(): void {
		$post_id = self::factory()->post->create();

		update_post_meta( $post_id, Settings::OWN_LINK_LAST_PROCESSED, time() );

		$this->assertSame( '', $this->render_archived_column( 'title', $post_id ) );
		$this->assertSame( '', $this->render_archived_column( WP_Post_Table_Controller::LINK_COLUMN_KEY, $post_id ) );
	}

	/**
	 * @testdox The archived column should be offered for post types that get archived, and withheld from those that do not. (#351)
	 *
	 * @return void
	 */
	public function test_add_column_registers_the_archived_column_for_archived_post_types(): void {
		$controller = new WP_Post_Table_Controller();

		update_option( Settings::ALLOWED_OWN_CONTENT_POST_TYPES, array( 'post' ) );
		$_REQUEST['post_type'] = 'post';

		$columns = $controller->add_column( array( 'title' => 'Title' ) );
		$this->assertArrayHasKey( WP_Post_Table_Controller::ARCHIVED_COLUMN_KEY, $columns );
		$this->assertSame( 'Last Archived', $columns[ WP_Post_Table_Controller::ARCHIVED_COLUMN_KEY ] );

		$_REQUEST['post_type'] = 'page';

		$columns = $controller->add_column( array( 'title' => 'Title' ) );
		$this->assertArrayNotHasKey( WP_Post_Table_Controller::ARCHIVED_COLUMN_KEY, $columns );

		unset( $_REQUEST['post_type'] );
		delete_option( Settings::ALLOWED_OWN_CONTENT_POST_TYPES );
	}

	/**
	 * @testdox The links column should still be offered independently of the archived column. (#351)
	 *
	 * @return void
	 */
	public function test_add_column_keeps_the_links_column_independent(): void {
		$controller = new WP_Post_Table_Controller();

		// Links are scanned on posts, but posts are not auto archived.
		update_option( Settings::ALLOWED_POST_TYPES, array( 'post' ) );
		update_option( Settings::ALLOWED_OWN_CONTENT_POST_TYPES, array( 'page' ) );
		$_REQUEST['post_type'] = 'post';

		$columns = $controller->add_column( array( 'title' => 'Title' ) );

		$this->assertArrayHasKey( WP_Post_Table_Controller::LINK_COLUMN_KEY, $columns );
		$this->assertArrayNotHasKey( WP_Post_Table_Controller::ARCHIVED_COLUMN_KEY, $columns );

		unset( $_REQUEST['post_type'] );
		delete_option( Settings::ALLOWED_POST_TYPES );
		delete_option( Settings::ALLOWED_OWN_CONTENT_POST_TYPES );
	}

	/**
	 * @testdox The archived column should be hidden by default on the list screens it appears on. (#351)
	 *
	 * @return void
	 */
	public function test_archived_column_is_hidden_by_default(): void {
		$controller = new WP_Post_Table_Controller();

		update_option( Settings::ALLOWED_OWN_CONTENT_POST_TYPES, array( 'post' ) );

		$screen            = \WP_Screen::get( 'edit-post' );
		$screen->post_type = 'post';

		$hidden = $controller->hide_archived_column_by_default( array(), $screen );

		$this->assertContains( WP_Post_Table_Controller::ARCHIVED_COLUMN_KEY, $hidden );

		delete_option( Settings::ALLOWED_OWN_CONTENT_POST_TYPES );
	}

	/**
	 * @testdox It should leave the hidden column list alone for post types the column is not added to. (#351)
	 *
	 * @return void
	 */
	public function test_archived_column_default_hiding_ignores_other_post_types(): void {
		$controller = new WP_Post_Table_Controller();

		update_option( Settings::ALLOWED_OWN_CONTENT_POST_TYPES, array( 'post' ) );

		$screen            = \WP_Screen::get( 'edit-page' );
		$screen->post_type = 'page';

		$this->assertSame( array(), $controller->hide_archived_column_by_default( array(), $screen ) );

		delete_option( Settings::ALLOWED_OWN_CONTENT_POST_TYPES );
	}

	/**
	 * @testdox It should leave the hidden column list alone on screens that are not a post list. (#351)
	 *
	 * @return void
	 */
	public function test_archived_column_default_hiding_ignores_other_screens(): void {
		$controller = new WP_Post_Table_Controller();

		update_option( Settings::ALLOWED_OWN_CONTENT_POST_TYPES, array( 'post' ) );

		$screen            = \WP_Screen::get( 'post' );
		$screen->post_type = 'post';

		$this->assertSame( array( 'existing' ), $controller->hide_archived_column_by_default( array( 'existing' ), $screen ) );

		delete_option( Settings::ALLOWED_OWN_CONTENT_POST_TYPES );
	}
}
