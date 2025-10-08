<?php

/**
 * Tests for the WP_Post_Controller class.
 *
 * @since 1.2.0
 */
declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Tests\Processor;

use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;
use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link_Repository;
use Internet_Archive\Wayback_Machine_Link_Fixer\WP_Post\WP_Post_Controller;

/**
 * Test_WP_Post_Controller
 */
class Test_WP_Post_Controller extends \WP_UnitTestCase {

	/**
	 * On set_up, clear all used filters and hooks
	 *
	 * @return void
	 */
	public function set_up(): void {
		// Delete all rows from actionscheduler_actions.
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}actionscheduler_actions" );

		// Clear all filters.
		\remove_all_filters( 'iawmlf_add_own_content_to_wayback_machine' );
		\remove_all_filters( 'iawmlf_own_content_post_types' );
		\remove_all_filters( 'iawmlf_own_content_allow_post' );

		parent::set_up();
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
		$handler->process_links_in_content( $post_id );

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
		$handler->on_save_post_process_post_links( $post_id, get_post( $post_id ), true );

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
		$handler->on_save_post_process_post_links( $post_id, get_post( $post_id ), true );

		// Get the post meta.
		$meta = get_post_meta( $post_id, Settings::LINK_META_KEY, true );
		$this->assertNotEmpty( $meta );
		$this->assertIsArray( $meta );
		$this->assertCount( 2, $meta );
	}

	/**
	 * @testdox If its set to not process links, do not add them to the meta.
	 *
	 * @return void
	 */
	public function test_not_process_links_not_added_to_meta(): void {
		update_option( Settings::PROCESS_LINKS, false );

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
		$handler->on_save_post_process_post_links( $post_id, get_post( $post_id ), true );

		// Get the post meta.
		$meta = get_post_meta( $post_id, Settings::LINK_META_KEY, true );
		$this->assertEmpty( $meta );

		// Revert
		update_option( Settings::PROCESS_LINKS, true );
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
		$handler->on_save_post_process_post_links( $post_id, get_post( $post_id ), true );

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
		$this->assertArrayHasKey( 'fixerOption', $data );

		// Check we have 0 links
		$this->assertEmpty( json_decode( $data['links'], true ) );

		// Clean up.
		unset( $GLOBALS['post'] );
	}

	/**
	 * @testdox When a post is saved, if we do not allow existing links to be processed, do not add them to the meta.
	 *
	 * @return void
	 */
	public function test_existing_links_not_processed(): void {

		// Set the option to not allow the post to be processed.
		add_filter( 'iawmlf_add_own_content_to_wayback_machine', '__return_false' );

		$post = \WP_UnitTestCase_Base::factory()->post->create_and_get();

		$handler = new WP_Post_Controller();
		$handler->on_save_post_process_post_links( $post->ID, $post, false );

		// The post should not be added to the action scheduler.
		global $wpdb;
		// Check that the action has been added to the queue.
		$actions = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}actionscheduler_actions where status='pending'" );

		// There should be no results.
		$this->assertCount( 0, $actions );
	}

	/**
	 * @testdox When a post is saved, if the post is not in the allowed post types, do not add the links to the meta.
	 *
	 * @return void
	 */
	public function test_not_allowed_post_types_not_processed(): void {
		// Alloq posts to be added.
		add_filter( 'iawmlf_add_own_content_to_wayback_machine', '__return_true' );

		// Only allow pages
		add_filter(
			'iawmlf_own_content_post_types',
			function ( $post_types ) {
				$post_types = array( 'page' );
				return $post_types;
			}
		);

		// Create a post with another post type.
		$post = \WP_UnitTestCase_Base::factory()->post->create_and_get();

		$handler = new WP_Post_Controller();
		$handler->on_save_post_process_post_links( $post->ID, $post, false );

		// The post should not be added to the action scheduler.
		global $wpdb;
		// Check that the action has been added to the queue.
		$actions = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}actionscheduler_actions where status='pending'" );

		// There should be no results.
		$this->assertCount( 0, $actions );
	}

	/**
	 * @testdox When a post is saved, if the post is not published, do not add the links to the meta.
	 *
	 * @return void
	 */
	public function test_not_published_post_not_processed(): void {
		// Alloq posts to be added.
		add_filter( 'iawmlf_add_own_content_to_wayback_machine', '__return_true' );

		// Only allow pages
		add_filter(
			'iawmlf_own_content_post_types',
			function ( $post_types ) {
				$post_types = array( 'page', 'post' );
				return $post_types;
			}
		);

		// Create a post with another post type.
		$post = \WP_UnitTestCase_Base::factory()->post->create_and_get( array( 'post_status' => 'draft' ) );

		$handler = new WP_Post_Controller();
		$handler->on_save_post_process_post_links( $post->ID, $post, false );

		// The post should not be added to the action scheduler.
		global $wpdb;
		// Check that the action has been added to the queue.
		$actions = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}actionscheduler_actions where status='pending'" );

		// There should be no results.
		$this->assertCount( 0, $actions );
	}

	/**
	 * @testdox If a post is saved, we allow to add own links, its in the allowed post types and its published, add the post to the action scheduler.
	 *
	 * @return void
	 */
	public function test_add_own_post_to_wayback_machine(): void {
		// Alloq posts to be added.
		add_filter( 'iawmlf_add_own_content_to_wayback_machine', '__return_true' );

		// Only allow pages
		add_filter(
			'iawmlf_own_content_post_types',
			function ( $post_types ) {
				$post_types = array( 'page', 'post' );
				return $post_types;
			}
		);

		// Create a post with another post type.
		$post = \WP_UnitTestCase_Base::factory()->post->create_and_get( array( 'post_status' => 'publish' ) );

		$handler = new WP_Post_Controller();
		$handler->on_save_post_process_post_links( $post->ID, $post, false );

		// The post should not be added to the action scheduler.
		global $wpdb;
		// Check that the action has been added to the queue.
		$actions = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}actionscheduler_actions where status='pending'" );

		// There should be no results.
		$this->assertCount( 1, $actions );

		$this->assertSame( json_decode( $actions[0]->args )->post_id, $post->ID );
	}

	/**
	 * @testdox It should be possible to disable a post being added to the wayback machine, using a filter on a post by post basis.
	 *
	 * @return void
	 */
	public function test_disable_own_post_to_wayback_machine(): void {
		// Allow posts to be added and allow post and page post types.
		add_filter( 'iawmlf_add_own_content_to_wayback_machine', '__return_true' );
		add_filter(
			'iawmlf_own_content_post_types',
			function ( $post_types ) {
				$post_types = array( 'page', 'post' );
				return $post_types;
			}
		);
		add_filter(
			'iawmlf_own_content_allow_post',
			function ( $allowed, $post ) {
				// If the post title is 'disable', return false.
				return $post->post_title !== 'disable';
			},
			10,
			2
		);

		// Create 2 posts, including one with the title 'disable'.
		$allowed_posts = \WP_UnitTestCase_Base::factory()->post->create_and_get();
		$disable_post  = \WP_UnitTestCase_Base::factory()->post->create_and_get( array( 'post_title' => 'disable' ) );

		$handler = new WP_Post_Controller();
		$handler->on_save_post_process_post_links( $allowed_posts->ID, $allowed_posts, false );
		$handler->on_save_post_process_post_links( $disable_post->ID, $disable_post, false );

		// The only post that should be added to the action scheduler is the first one.
		global $wpdb;
		// Check that the action has been added to the queue.
		$actions = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}actionscheduler_actions where status='pending'" );

		// There should be 1 result.
		$this->assertCount( 1, $actions );

		$this->assertSame( json_decode( $actions[0]->args )->post_id, $allowed_posts->ID );
	}
}
