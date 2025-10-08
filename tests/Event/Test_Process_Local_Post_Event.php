<?php

/**
 * Tests for Check Process local post event
 *
 * @since 1.3.0
 *
 * @coversDefaultClass \WPCOMSpecialProjects\Wayback_Link_Fixer\Event\Process_Local_Post_Event
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer_Tests\Tests\Event;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Event\Process_Local_Post_Event;
use WPCOMSpecialProjects\Wayback_Link_Fixer_Tests\Tools\Wayback_Machine_Helper;

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Test_Process_Local_Post_Event
 */
class Test_Process_Local_Post_Event extends TestCase {

	use Wayback_Machine_Helper;


	private $wpdb;

	/**
	 * Setup
	 */
	public function set_up(): void {
		$this->wpdb = $GLOBALS['wpdb'];

		// Clear the actionscheduler_actions table.
		$this->wpdb->query( "TRUNCATE TABLE {$this->wpdb->prefix}actionscheduler_actions" );

		$this->clear_clients();

		\remove_all_filters( 'post_link' );

		parent::set_up();
	}

	/**
	 * @testdox  It should be possible to add a post to the queue and have it added instantly.
	 *
	 * @return void
	 */
	public function test_add_local_post_to_queue(): void {
		$post_id = 1;

		// Create the event.
		$event = new Process_Local_Post_Event();

		$event::add_to_queue( $post_id );

		// Check that the action has been added to the queue.
		$actions = $this->wpdb->get_results( "SELECT * FROM {$this->wpdb->prefix}actionscheduler_actions" );

		$this->assertCount( 1, $actions );

		// Get the action.
		$action = $actions[0];

		$this->assertSame( 'iawmlf_process_local_post', $action->hook );
		$this->assertSame( 'pending', $action->status );
		$this->assertSame( \json_encode( array( 'post_id' => 1 ) ), $action->args );
	}

	/**
	 * @testdox Even if the same post is added to the queue 5 times, it should only be added once.
	 *
	 * @return void
	 */
	public function test_add_local_post_to_queue_multiple_times(): void {
		$post_id = 1;

		// Create the event.
		$event = new Process_Local_Post_Event();

		$event::add_to_queue( $post_id );
		$event::add_to_queue( $post_id );
		$event::add_to_queue( $post_id );
		$event::add_to_queue( $post_id );
		$event::add_to_queue( $post_id );

		// Check that the action has been added to the queue.
		$actions = $this->wpdb->get_results( "SELECT * FROM {$this->wpdb->prefix}actionscheduler_actions where status='pending'" );
		$this->assertCount( 1, $actions );
	}

	/**
	 * @testdox It should be possible to add an event with a defined delay.
	 *
	 * @return void
	 */
	public function test_add_local_post_to_queue_with_delay(): void {
		$post_id = 2;

		$event = new Process_Local_Post_Event();

		$event::add_to_queue_with_delay( $post_id, DAY_IN_SECONDS );

		$current_time = time();

		// Check that the action has been added to the queue.
		$actions = $this->wpdb->get_results( "SELECT * FROM {$this->wpdb->prefix}actionscheduler_actions where status='pending'" );

		// Expect the time to be within 59mins and 61mins.
		$this->assertGreaterThan( $current_time + DAY_IN_SECONDS - 60, \strtotime( $actions[0]->scheduled_date_gmt ) );
		$this->assertLessThan( $current_time + DAY_IN_SECONDS + 60, \strtotime( $actions[0]->scheduled_date_gmt ) );
	}

	/**
	 * @testdox If no delay time is added, it should default to 1 hour.
	 *
	 * @return void
	 */
	public function test_add_local_post_to_queue_with_default_delay(): void {
		$post_id = 3;

		$event = new Process_Local_Post_Event();

		$event::add_to_queue_with_delay( $post_id );

		$current_time = time();

		// Check that the action has been added to the queue.
		$actions = $this->wpdb->get_results( "SELECT * FROM {$this->wpdb->prefix}actionscheduler_actions where status='pending'" );

		// Expect the time to be within 59mins and 61mins.
		$this->assertGreaterThan( $current_time + HOUR_IN_SECONDS - 60, \strtotime( $actions[0]->scheduled_date_gmt ) );
		$this->assertLessThan( $current_time + HOUR_IN_SECONDS + 60, \strtotime( $actions[0]->scheduled_date_gmt ) );
	}

	/**
	 * @testdox When processing a post, if WBM is offline, it should throw an exception.
	 *
	 * @return void
	 */
	public function test_process_local_post_event_offline_throws_exception(): void {
		$this->create_service( false );

		$event = new Process_Local_Post_Event();

		$this->expectException( \Exception::class );

		$event( 4 );
	}


	/**
	 * @testdox When processing a post, if WBM is offline, it should add the event to the queue.
	 *
	 * @return void
	 */
	public function test_process_local_post_event_offline_adds_to_queue(): void {
		$this->create_service( false );

		$event = new Process_Local_Post_Event();

		try {
			$event( 5 );
		} catch ( \Throwable $th ) {
			//throw $th;
		} finally {
			// Check that the action has been added to the queue.
			$actions = $this->wpdb->get_results( "SELECT * FROM {$this->wpdb->prefix}actionscheduler_actions where status='pending'" );

			$this->assertCount( 1, $actions );
		}
	}

	/**
	 * @testdox When processing a post, if the post is not found, it should throw an exception.
	 *
	 * @return void
	 */
	public function test_process_local_post_event_post_not_found_throws_exception(): void {
		$this->create_service();

		$event = new Process_Local_Post_Event();

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Post not found for id:6' );
		$event( 600000 );
	}

	/**
	 * @testdox When processing a post, if the posts permalink cant be found, it should throw an exception.
	 *
	 * @return void
	 */
	public function test_process_local_post_event_permalink_not_found_throws_exception(): void {
		$this->create_service();

		// Create a post with no permalink.
		$post_id = \wp_insert_post(
			array(
				'post_title'   => 'Test Post',
				'post_content' => 'Test Content',
				'post_status'  => 'publish',
			)
		);

		add_filter( 'post_link', '__return_false', 10, 3 );

		$event = new Process_Local_Post_Event();

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Permalink not found for post id:' . $post_id );
		$event( $post_id );
	}


	/**
	 * @testdox If a valid post is processed, a snapshot should be created and the post meta updated to set the last updated time.
	 *
	 * @return void
	 */
	public function test_process_local_post_event_valid_post(): void {
		$processed = array();

		$this->create_service(
			true,
			function ( array $config ) use ( &$processed ): array {
				$config['snapshot']
					->method( 'create_snapshot' )
					->willReturnCallback(
						function ( string $url ) use ( &$processed ): string {
							$processed[] = $url;
							return $url;
						}
					);

				return $config;
			}
		);

		$post_id = \wp_insert_post(
			array(
				'post_title'   => 'Test Post',
				'post_content' => 'Test Content',
				'post_status'  => 'publish',
			)
		);

		$event = new Process_Local_Post_Event();
		$event( $post_id );

		// Check the snapshot was created.
		$this->assertNotEmpty( $processed );
		$this->assertSame( 'http://example.org/?p=' . $post_id, $processed[0] );

		// Check the last updated time was set.
		$meta = get_post_meta( $post_id, Settings::OWN_LINK_LAST_PROCESSED, true );

		$this->assertNotEmpty( $meta );
		$this->assertIsNumeric( $meta );

		// Ensure the time is within 10 seconds of the current time.
		$this->assertLessThan( 10, abs( time() - $meta ) );
		$this->assertGreaterThan( -10, abs( time() - $meta ) );
	}

}
