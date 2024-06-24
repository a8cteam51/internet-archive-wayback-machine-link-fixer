<?php

/**
 * Tests for the WP_Post_Controller class.
 *
 * @since 1.2.0
 *
 * @coversDefaultClass \WPCOMSpecialProjects\Wayback_Link_Fixer\Processor\Post_Handler
 */
declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Tests\Processor;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link_Repository;
use WPCOMSpecialProjects\Wayback_Link_Fixer\WP_Post\WP_Post_Controller;

/**
 * Test_WP_Post_Controller
 */
class Test_WP_Post_Controller extends \WP_UnitTestCase {

	/**
	 * On tear down, ensure all localised data is removed.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		// Remove any global post.
		// unset( $GLOBALS['post'] );

		// Clear all enqueued scripts.
		// wp_dequeue_script( 'wpcomsp-wayback-link-fixer-front-link-checker' );
	}

	/**
	 * @testdox It should be possible to create a post that has links in the contents and have the links added to the meta array.
	 *
	 * @return void
	 */
	public function test_can_add_links_to_post_meta(): void {

		$post_id = \WP_UnitTestCase_Base::factory()->post->create();

		// Add some content to the post.
		$content = 'This is a post with a link to <a href="https://from.post/content">example</a><br>And another link to <a href="https://from.post/content_twice">example</a>';
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $content,
			)
		);

		$handler = new WP_Post_Controller();
		$handler->process_single_post( $post_id );

		// Get the post meta.
		$meta = get_post_meta( $post_id, Settings::LINK_META_KEY, true );
		$this->assertIsArray( $meta );

		// Check the link.
		$db_links = ( new Link_Repository() )
			->get_links_for_post( $post_id )
			->get_links();

		$this->assertCount( 2, $db_links );

		// Get the ids.
		$ids = array_map(
			function ( $link ) {
				return $link->get_id();
			},
			$db_links
		);

		// Check the links are in the meta.
		$this->assertContains( $ids[0], $meta );
		$this->assertContains( $ids[1], $meta );
	}

	/**
	 * @testdox Only posts which are in the allows list, should have links added to the meta.
	 *
	 * @return void
	 */
	public function test_only_allowed_posts_have_links_added_to_meta(): void {
		$post_id = \WP_UnitTestCase_Base::factory()->post->create();

		// Add some content to the post.
		$content = 'This is a post with a link to <a href="https://from.post/content">example</a><br>And another link to <a href="https://from.post/content_twice">example</a>';
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $content,
				'post_type'    => 'not_valid',
			)
		);

		$handler = new WP_Post_Controller();
		$handler->on_save_post( $post_id, get_post( $post_id ), true );

		// Get the post meta.
		$meta = get_post_meta( $post_id, Settings::LINK_META_KEY, true );
		$this->assertEmpty( $meta );
	}

	/**
	 * @testdox Post types which are allowed should have links added to the meta.
	 *
	 * @return void
	 */
	public function test_allowed_post_types_have_links_added_to_meta(): void {
		$post_id = \WP_UnitTestCase_Base::factory()->post->create();

		// Add some content to the post.
		$content = 'This is a post with a link to <a href="https://from.post/content">example</a><br>And another link to <a href="https://from.post/content_twice">example</a>';
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $content,
				'post_type'    => 'post',
			)
		);

		$handler = new WP_Post_Controller();
		$handler->on_save_post( $post_id, get_post( $post_id ), true );

		// Get the post meta.
		$meta = get_post_meta( $post_id, Settings::LINK_META_KEY, true );
		$this->assertNotEmpty( $meta );
		$this->assertIsArray( $meta );
		$this->assertCount( 2, $meta );
	}

	/**
	 * @testdox When called from the front end, the front end script should be enqueued with all the posts links passed in a localized data.
	 * @group localized-data
	 * @return void
	 */
	public function test_front_end_script_is_enqueued(): void {
		// Clear the wp-scripts global.
		wp_scripts()->registered = array();

		$post_id = \WP_UnitTestCase_Base::factory()->post->create();

		$GLOBALS['post'] = get_post( $post_id );

		// Add some content to the post.
		$content = 'This is a post with a link to <a href="https://from.post/content">example</a><br>And another link to <a href="https://from.post/content_twice">example</a>';
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $content,
				'post_type'    => 'post',
			)
		);

		$handler = new WP_Post_Controller();
		$handler->on_save_post( $post_id, get_post( $post_id ), true );

		// Get the post meta.
		$meta = get_post_meta( $post_id, Settings::LINK_META_KEY, true );
		$this->assertNotEmpty( $meta );
		$this->assertIsArray( $meta );
		$this->assertCount( 2, $meta );

		// Enqueue the script.
		$handler->enqueue_frontend_script();

		// Trigger the action.
		do_action( 'wp_enqueue_scripts' );

		// Check the script is enqueued.
		$this->assertTrue( wp_script_is( 'wpcomsp-wayback-link-fixer-front-link-checker' ) );

		// Check the localized data.
		$localized_data = wp_scripts()->get_data( 'wpcomsp-wayback-link-fixer-front-link-checker', 'data' );

		// Clean up.
		unset( $GLOBALS['post'] );

		// Check starts with var wlfArchivedLinks =
		$this->assertStringStartsWith( 'var wlfArchivedLinks = ', $localized_data );

		// Extract everything between first and last {}
		$matches = array();
		preg_match( '/\{.*\}/', $localized_data, $matches );
		$data = json_decode( $matches[0], true );

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'linkCheckNonce', $data );
		$this->assertArrayHasKey( 'linkDelayInDays', $data );
		$this->assertArrayHasKey( 'linkCheckAjax', $data );
		$this->assertArrayHasKey( 'links', $data );
		$this->assertArrayHasKey( 'ajaxUrl', $data );

		// Check we have 2 links
		$this->assertCount( 2, json_decode( $data['links'], true ) );
	}

	/**
	 * @testdox When a post has no links and its not been scanned, do not attempt to add any links to the localized data.
	 * @group localized-data
	 * @see https://github.com/a8cteam51/wayback-link-fixer/issues/48
	 * @since 1.1.1-m "TR-m "
	 *
	 * @return void
	 */
	public function test_no_links_no_localized_data(): void {
		// Clear the wp-scripts global.
		wp_scripts()->registered = array();

		// Create a post with no links and set as the global post (we set an invalid ID to mock unprocessed post)
		$post_id         = \WP_UnitTestCase_Base::factory()->post->create() + 9999;
		$GLOBALS['post'] = get_post( $post_id );

		$handler = new WP_Post_Controller();

		// Get the post meta.
		$meta = get_post_meta( $post_id, Settings::LINK_META_KEY, true );
		$this->assertEquals( '', $meta );

		// Enqueue the script.
		$handler->enqueue_frontend_script();

		// Trigger the action.
		do_action( 'wp_enqueue_scripts' );

		// Check the script is enqueued.
		$this->assertTrue( wp_script_is( 'wpcomsp-wayback-link-fixer-front-link-checker' ) );

		// Check the localized data.
		$localized_data = wp_scripts()->get_data( 'wpcomsp-wayback-link-fixer-front-link-checker', 'data' );

		// Clean up.
		unset( $GLOBALS['post'] );

		// Check starts with var wlfArchivedLinks =
		$this->assertStringStartsWith( 'var wlfArchivedLinks = ', $localized_data );

		// Extract everything between first and last {}
		$matches = array();
		preg_match( '/\{.*\}/', $localized_data, $matches );
		$data = json_decode( $matches[0], true );

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'linkCheckNonce', $data );
		$this->assertArrayHasKey( 'linkDelayInDays', $data );
		$this->assertArrayHasKey( 'linkCheckAjax', $data );
		$this->assertArrayHasKey( 'links', $data );
		$this->assertArrayHasKey( 'ajaxUrl', $data );

		// Check we have 0 links
		$this->assertEmpty( json_decode( $data['links'], true ) );

		// Clean up.
		unset( $GLOBALS['post'] );
	}
}
