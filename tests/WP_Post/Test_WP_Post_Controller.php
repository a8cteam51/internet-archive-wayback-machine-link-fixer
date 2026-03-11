<?php

/**
 * Tests for the WP_Post_Controller class.
 *
 * @since 1.2.0
 *
 * @coversDefaultClass \Internet_Archive\Wayback_Machine_Link_Fixer\WP_Post\WP_Post_Controller
 */
declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Tests\Processor;

use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;
use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link_Exclusion;
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

		// Reset the wp scripts globals
		$GLOBALS['wp_scripts'] = new \WP_Scripts();
		wp_default_scripts( $GLOBALS['wp_scripts'] );

		parent::set_up();
	}

	/**
	 * Tare Down
	 *
	 * @return void
	 */
	public function tear_down(): void {
		// Reset the wp scripts globals
		$GLOBALS['wp_scripts'] = new \WP_Scripts();
		wp_default_scripts( $GLOBALS['wp_scripts'] );

		parent::tear_down();
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
		$this->assertTrue( wp_script_is( 'iawm-link-fixer-front-link-checker' ) );

		// Check the localized data.
		$localized_data = wp_scripts()->get_data( 'iawm-link-fixer-front-link-checker', 'data' );

		// Clean up.
		unset( $GLOBALS['post'] );

		// Check starts with var iawmlfArchivedLinks =
		$this->assertStringStartsWith( 'var iawmlfArchivedLinks = ', $localized_data );

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
		$this->assertTrue( wp_script_is( 'iawm-link-fixer-front-link-checker' ) );

		// Check the localized data.
		$localized_data = wp_scripts()->get_data( 'iawm-link-fixer-front-link-checker', 'data' );

		// Clean up.
		unset( $GLOBALS['post'] );

		// Check starts with var iawmlfArchivedLinks =
		$this->assertStringStartsWith( 'var iawmlfArchivedLinks = ', $localized_data );

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

	/**
	 * @testdox It should be possible to clear all the post meta, this can then be used to clear on uninistall.
	 *
	 * @return void
	 */
	public function test_can_clear_all_post_meta(): void {
		// Create multiple posts in multiple post types with meta.
		$post_id_1 = \WP_UnitTestCase_Base::factory()->post->create();
		$post_id_2 = \WP_UnitTestCase_Base::factory()->post->create( array( 'post_type' => 'page' ) );
		$post_id_3 = \WP_UnitTestCase_Base::factory()->post->create( array( 'post_type' => 'custom' ) );

		// Add the meta.
		update_post_meta( $post_id_1, Settings::LINK_META_KEY, array( 1, 2, 3 ) );
		update_post_meta( $post_id_1, Settings::OWN_LINK_LAST_PROCESSED, time() );
		update_post_meta( $post_id_2, Settings::LINK_META_KEY, array( 4, 5, 6 ) );
		update_post_meta( $post_id_2, Settings::OWN_LINK_LAST_PROCESSED, time() );
		update_post_meta( $post_id_3, Settings::LINK_META_KEY, array( 7, 8, 9 ) );
		update_post_meta( $post_id_3, Settings::OWN_LINK_LAST_PROCESSED, time() );

		// Clear all the post meta.
		WP_Post_Controller::clear_all_post_meta();

		// Check that the meta has been cleared.
		$this->assertEmpty( get_post_meta( $post_id_1, Settings::LINK_META_KEY ) );
		$this->assertEmpty( get_post_meta( $post_id_2, Settings::LINK_META_KEY ) );
		$this->assertEmpty( get_post_meta( $post_id_3, Settings::LINK_META_KEY ) );
		$this->assertEmpty( get_post_meta( $post_id_1, Settings::OWN_LINK_LAST_PROCESSED ) );
		$this->assertEmpty( get_post_meta( $post_id_2, Settings::OWN_LINK_LAST_PROCESSED ) );
		$this->assertEmpty( get_post_meta( $post_id_3, Settings::OWN_LINK_LAST_PROCESSED ) );
	}

	/**
	 * @testdox If a post is in the excluded posts list, process_links_in_content should bail silently without setting link meta.
	 *
	 * @return void
	 */
	public function test_excluded_post_not_processed_by_process_links_in_content(): void {
		$post_id = self::factory()->post->create();

		// Add some content to the post.
		$content = 'This is a post with a link to <a href="https://from.post/excluded">example</a>';
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $content,
			)
		);

		// Clear any meta that was set by the save_post hook.
		\delete_post_meta( $post_id, Settings::LINK_META_KEY );

		// Add the post to the exclusion list.
		\update_option( Settings::LINK_FIXER_EXCLUDED_POSTS, array( $post_id ) );

		// Process the post.
		$handler = new WP_Post_Controller();
		$handler->process_links_in_content( $post_id );

		// The post should NOT have link meta set.
		$this->assertFalse(
			\metadata_exists( 'post', $post_id, Settings::LINK_META_KEY ),
			'The excluded post should not have link meta set.'
		);

		// Clean up.
		\delete_option( Settings::LINK_FIXER_EXCLUDED_POSTS );
	}

	/**
	 * @testdox When an option is selected to fix links, it should be rendered out the HTML.
	 *
	 * @since 1.3.1
	 *
	 * @return void
	 */
	public function test_render_html_link_output(): void {
		// Set the option to render the HTML link output.
		update_option( Settings::FIXER_OPTION, Settings::FIXER_OPTION_REPLACE_LINK );

		$post_id = \WP_UnitTestCase_Base::factory()->post->create();

		$content = 'This is a post with a link to <a href="https://from.post/content">example</a><br>And another link to <a href="https://from.post/content_twice">example</a>';
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $content,
				'post_type'    => 'post',
			)
		);

		$GLOBALS['post'] = get_post( $post_id );

		// Render the block.
		$rendered = do_blocks( $GLOBALS['post']->post_content );

		// Check contains the link data script tag.
		$this->assertStringContainsString( '__iawmlf-post-loop-links', $rendered );

		unset( $GLOBALS['post'] );
	}

	/**
	 * @testdox If a post is in the excluded posts list, the link data attribute should not be rendered in the block output.
	 *
	 * @return void
	 */
	public function test_excluded_post_does_not_render_link_data(): void {
		// Set the option to render the HTML link output.
		update_option( Settings::FIXER_OPTION, Settings::FIXER_OPTION_REPLACE_LINK );

		$post_id = self::factory()->post->create();

		$content = 'This is a post with a link to <a href="https://from.post/excluded_render">example</a>';
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $content,
				'post_type'    => 'post',
			)
		);

		$GLOBALS['post'] = get_post( $post_id );

		// Add the post to the exclusion list.
		\update_option( Settings::LINK_FIXER_EXCLUDED_POSTS, array( $post_id ) );

		// Render the block.
		$rendered = do_blocks( $GLOBALS['post']->post_content );

		// Check does NOT contain the link data script tag.
		$this->assertStringNotContainsString( '__iawmlf-post-loop-links', $rendered );

		// Clean up.
		unset( $GLOBALS['post'] );
		\delete_option( Settings::LINK_FIXER_EXCLUDED_POSTS );
	}

	/**
	 * @testdox When a link matches a global exclusion pattern, it should not appear in the render_block data attribute output.
	 *
	 * @return void
	 */
	public function test_excluded_link_not_included_in_render_block_data(): void {
		// Set the option to render the HTML link output.
		update_option( Settings::FIXER_OPTION, Settings::FIXER_OPTION_REPLACE_LINK );

		// Add a global exclusion pattern that matches one of the links.
		update_option( Settings::LINK_EXCLUSIONS, array( '*excluded-domain.com*' ) );

		// Reset the Link_Exclusion static cache so it picks up the new option.
		$reflection = new \ReflectionClass( Link_Exclusion::class );
		$property   = $reflection->getProperty( 'exclusions' );
		$property->setAccessible( true );
		$property->setValue( null, null );

		$post_id = self::factory()->post->create();

		// Add content with two links — one that will be excluded, one that won't.
		$content = 'Link one <a href="https://excluded-domain.com/page">excluded</a> and link two <a href="https://allowed-domain.com/page">allowed</a>';
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $content,
				'post_type'    => 'post',
			)
		);

		$GLOBALS['post'] = get_post( $post_id );

		// Render the block.
		$rendered = do_blocks( $GLOBALS['post']->post_content );

		// The script tag should be present (we still have one non-excluded link).
		$this->assertStringContainsString( '__iawmlf-post-loop-links', $rendered );

		// Extract the JSON from the script tag.
		preg_match( "/<script[^>]*class='__iawmlf-post-loop-links'[^>]*>(.*?)<\/script>/s", $rendered, $matches );
		$this->assertNotEmpty( $matches, 'Should find the script tag in rendered output.' );

		$links_data = json_decode( $matches[1], true );
		$this->assertIsArray( $links_data );

		// Collect all hrefs from the links data.
		$hrefs = array_column( $links_data, 'href' );

		// The excluded link should NOT be in the data.
		$this->assertNotContains( 'https://excluded-domain.com/page', $hrefs, 'Excluded link should not appear in render_block data.' );

		// The allowed link SHOULD be in the data.
		$this->assertContains( 'https://allowed-domain.com/page', $hrefs, 'Non-excluded link should appear in render_block data.' );

		// Clean up.
		unset( $GLOBALS['post'] );
		\delete_option( Settings::LINK_EXCLUSIONS );

		// Reset the Link_Exclusion static cache.
		$property->setValue( null, null );
	}

	/**
	 * @testdox When a link matches a global exclusion pattern and another does not, only the non-excluded link should appear in the render_block data attribute output.
	 *
	 * @return void
	 */
	public function test_excluded_link_filtered_from_render_block_data_with_mixed_links(): void {
		// Set the option to render the HTML link output.
		update_option( Settings::FIXER_OPTION, Settings::FIXER_OPTION_REPLACE_LINK );

		// Add a global exclusion pattern that matches one of the links.
		update_option( Settings::LINK_EXCLUSIONS, array( '*excluded-domain.com*' ) );

		// Reset the Link_Exclusion static cache so it picks up the new option.
		$reflection = new \ReflectionClass( Link_Exclusion::class );
		$property   = $reflection->getProperty( 'exclusions' );
		$property->setAccessible( true );
		$property->setValue( null, null );

		$post_id = self::factory()->post->create();

		// Add content with two links — one excluded, one allowed.
		$content = 'Link one <a href="https://excluded-domain.com/page1">excluded</a> and link two <a href="https://kept-domain.com/page">kept</a>';
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $content,
				'post_type'    => 'post',
			)
		);

		$GLOBALS['post'] = get_post( $post_id );

		// Render the block.
		$rendered = do_blocks( $GLOBALS['post']->post_content );

		// The script tag should be present (we still have one non-excluded link).
		$this->assertStringContainsString( '__iawmlf-post-loop-links', $rendered );

		// Extract the JSON from the script tag.
		preg_match( "/<script[^>]*class='__iawmlf-post-loop-links'[^>]*>(.*?)<\/script>/s", $rendered, $matches );
		$this->assertNotEmpty( $matches, 'Should find the script tag in rendered output.' );

		$links_data = json_decode( $matches[1], true );
		$this->assertIsArray( $links_data );

		// Should only have 1 link (the non-excluded one).
		$this->assertCount( 1, $links_data, 'Only the non-excluded link should be in the data.' );

		// Collect all hrefs from the links data.
		$hrefs = array_column( $links_data, 'href' );

		// The excluded link should NOT be in the data.
		$this->assertNotContains( 'https://excluded-domain.com/page1', $hrefs, 'Excluded link should not appear in render_block data.' );

		// The allowed link SHOULD be in the data.
		$this->assertContains( 'https://kept-domain.com/page', $hrefs, 'Non-excluded link should appear in render_block data.' );

		// Clean up.
		unset( $GLOBALS['post'] );
		\delete_option( Settings::LINK_EXCLUSIONS );

		// Reset the Link_Exclusion static cache.
		$property->setValue( null, null );
	}

	/**
	 * @testdox When an option is selected to do nothing, it should not render out the HTML link output.
	 *
	 * @since 1.3.1
	 *
	 * @return void
	 */
	public function test_do_nothing_option_not_render_html_link_output(): void {
		// Set the option to do nothing.
		update_option( Settings::FIXER_OPTION, Settings::FIXER_OPTION_DO_NOTHING );

		$post_id = \WP_UnitTestCase_Base::factory()->post->create();

		$content = 'This is a post with a link to <a href="https://from.post/content">example</a><br>And another link to <a href="https://from.post/content_twice">example</a>';
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $content,
				'post_type'    => 'post',
			)
		);

		$GLOBALS['post'] = get_post( $post_id );

		// Render the block.
		$rendered = do_blocks( $GLOBALS['post']->post_content );

		// Check does not contain the link data script tag.
		$this->assertStringNotContainsString( '__iawmlf-post-loop-links', $rendered );

		unset( $GLOBALS['post'] );
	}

	/**
	 * @testdox When the HTML link output should not be rendered, it should not enqueue the script.
	 *
	 * @since 1.3.1
	 *
	 * @return void
	 */
	public function test_do_nothing_option_not_enqueue_script(): void {
		// Set the option to do nothing.
		update_option( Settings::FIXER_OPTION, Settings::FIXER_OPTION_DO_NOTHING );

		// Enqueue the script.
		$handler = new WP_Post_Controller();
		$handler->enqueue_frontend_script();
		do_action( 'wp_enqueue_scripts' );

		// Ensure the script is not enqueued.
		$enqueued_scripts = wp_scripts()->queue;

		$this->assertNotContains( 'iawm-link-fixer-front-link-checker', $enqueued_scripts );
	}

	/**
	 * @testdox A post in the auto archiver excluded posts list should not be added to the wayback machine.
	 *
	 * @return void
	 */
	public function test_excluded_auto_archiver_post_not_added_to_wayback_machine(): void {
		// Allow posts to be added.
		add_filter( 'iawmlf_add_own_content_to_wayback_machine', '__return_false' );
		add_filter(			'iawmlf_own_content_post_types',			fn () => array( 'post' )			);

		// Create 2 posts.
		$post_allowed  = self::factory()->post->create_and_get( array( 'post_status' => 'publish' ) );
		$post_excluded = self::factory()->post->create_and_get( array( 'post_status' => 'publish' ) );

		// Add the second post to the auto archiver exclusion list.
		\update_option( Settings::AUTO_ARCHIVER_EXCLUDED_POSTS, array( $post_excluded->ID ) );

		$handler = new WP_Post_Controller();
		$handler->add_own_post_to_wayback_machine( $post_allowed->ID );
		$handler->add_own_post_to_wayback_machine( $post_excluded->ID );

		// Get all pending action scheduler actions.
		global $wpdb;
		$actions = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}actionscheduler_actions WHERE status='pending'" );

		// Should be 1 action (only the allowed post).
		$this->assertCount( 1, $actions );
		$this->assertSame( $post_allowed->ID, json_decode( $actions[0]->args )->post_id );

		// Clean up.
		\delete_option( Settings::AUTO_ARCHIVER_EXCLUDED_POSTS );
	}

	/**
	 * @testdox When a post is saved via on_save_post_process_own_post and is in the auto archiver exclusion list, it should not be queued.
	 *
	 * @return void
	 */
	public function test_on_save_excluded_auto_archiver_post_not_queued(): void {
		// Enable own content submissions.
		add_filter( 'iawmlf_add_own_content_to_wayback_machine', '__return_true' );
		add_filter(
			'iawmlf_own_content_post_types',
			fn () => array( 'post' )
		);

		// Create 2 published posts — save_post fires and both should be queued.
		$post_allowed  = self::factory()->post->create_and_get( array( 'post_status' => 'publish' ) );
		$post_excluded = self::factory()->post->create_and_get( array( 'post_status' => 'publish' ) );

		// Assert both posts were queued on creation.
		global $wpdb;
		$actions = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}actionscheduler_actions" );
		$this->assertCount( 2, $actions, 'Both posts should be queued on initial save.' );

		// Now add the second post to the exclusion list.
		\update_option( Settings::AUTO_ARCHIVER_EXCLUDED_POSTS, array( $post_excluded->ID ) );

		// Re-save both posts — save_post hook fires on_save_post_process_own_post naturally.
		wp_update_post( array( 'ID' => $post_allowed->ID, 'post_title' => 'Updated allowed' ) );
		wp_update_post( array( 'ID' => $post_excluded->ID, 'post_title' => 'Updated excluded' ) );

		// The allowed post's original action was cancelled by ensure_single_event and a new one added.
		// The excluded post's original action is still pending (untouched).
		// So we expect: 2 pending (excluded original + allowed new), 1 cancelled (allowed original).
		$pending = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}actionscheduler_actions WHERE status='pending'" );
		$this->assertCount( 2, $pending, 'Should have 2 pending actions after re-save.' );

		$cancelled = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}actionscheduler_actions WHERE status='canceled'" );
		$this->assertCount( 1, $cancelled, 'The allowed post original action should be cancelled by ensure_single_event.' );

		// Clean up.
		\delete_option( Settings::AUTO_ARCHIVER_EXCLUDED_POSTS );
	}
}
