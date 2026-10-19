<?php

/**
 * Tests for the Orphaned_Link_Clean_Up_Event class.
 *
 * @since 1.5.0
 *
 * @coversDefaultClass \Internet_Archive\Wayback_Machine_Link_Fixer\Event\Orphaned_Link_Clean_Up_Event
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Tests\Event;

use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link;
use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;
use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link_Repository;
use Internet_Archive\Wayback_Machine_Link_Fixer\Event\Event_Controller;
use Internet_Archive\Wayback_Machine_Link_Fixer\Event\Orphaned_Link_Clean_Up_Event;

/**
 * Test_Orphaned_Link_Clean_Up_Event
 */
class Test_Orphaned_Link_Clean_Up_Event extends \WP_UnitTestCase {

	/**
	 * Link Repository instance.
	 *
	 * @var Link_Repository
	 */
	private $link_repository;

	public function set_up(): void {
		parent::set_up();

		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}actionscheduler_actions" );

		update_option( Settings::CLEAN_UP_ORPHANED_LINKS, true );

		$this->link_repository = new Link_Repository();
	}

	public function tear_down(): void {
		delete_option( Settings::CLEAN_UP_ORPHANED_LINKS );

		parent::tear_down();
	}

	/**
	 * Creates a done link, optionally used by a post.
	 *
	 * @param string       $url     The link url.
	 * @param integer|null $post_id The post using the link.
	 *
	 * @return Link
	 */
	private function create_done_link( string $url, ?int $post_id = null ): Link {
		$link = $this->link_repository->upsert( ( new Link( $url ) )->set_done() );

		if ( null !== $post_id ) {
			update_post_meta( $post_id, Settings::LINK_META_KEY, array( $link->get_id() ) );
		}

		return $link;
	}

	/**
	 * The pending clean-up actions.
	 *
	 * @return \ActionScheduler_Action[]
	 */
	private function pending_actions(): array {
		return array_values(
			\as_get_scheduled_actions(
				array(
					'hook'     => Orphaned_Link_Clean_Up_Event::HANDLE,
					'status'   => \ActionScheduler_Store::STATUS_PENDING,
					'per_page' => -1,
				)
			)
		);
	}

	/**
	 * @testdox A done link no post uses is deleted, and a link a post uses is kept.
	 *
	 * @return void
	 */
	public function test_unused_links_are_deleted_and_used_links_kept(): void {
		$unused = $this->create_done_link( 'https://orphan-unused.com' );
		$used   = $this->create_done_link( 'https://orphan-used.com', self::factory()->post->create() );

		( new Orphaned_Link_Clean_Up_Event() )( $unused->get_id() - 1 );

		$this->assertNull( $this->link_repository->find_by_id( $unused->get_id() ) );
		$this->assertNotNull( $this->link_repository->find_by_id( $used->get_id() ) );
	}

	/**
	 * @testdox A link used only by a trashed post is kept, as the post can be restored.
	 *
	 * @return void
	 */
	public function test_links_in_trashed_posts_are_kept(): void {
		$post_id = self::factory()->post->create();
		$link    = $this->create_done_link( 'https://orphan-trashed.com', $post_id );
		wp_trash_post( $post_id );

		( new Orphaned_Link_Clean_Up_Event() )( $link->get_id() - 1 );

		$this->assertNotNull( $this->link_repository->find_by_id( $link->get_id() ) );
	}

	/**
	 * @testdox A new or pending link no post uses yet is kept, as it may be mid way through being added to a post.
	 *
	 * @return void
	 */
	public function test_links_not_done_are_kept(): void {
		$new     = $this->link_repository->upsert( new Link( 'https://orphan-new.com' ) );
		$pending = $this->link_repository->upsert( ( new Link( 'https://orphan-pending.com' ) )->set_pending() );

		( new Orphaned_Link_Clean_Up_Event() )( $new->get_id() - 1 );

		$this->assertNotNull( $this->link_repository->find_by_id( $new->get_id() ) );
		$this->assertNotNull( $this->link_repository->find_by_id( $pending->get_id() ) );
	}

	/**
	 * @testdox With the setting off, nothing is deleted and nothing is queued.
	 *
	 * @return void
	 */
	public function test_does_nothing_when_setting_is_off(): void {
		update_option( Settings::CLEAN_UP_ORPHANED_LINKS, false );
		$unused = $this->create_done_link( 'https://orphan-setting-off.com' );

		( new Orphaned_Link_Clean_Up_Event() )( $unused->get_id() - 1 );

		$this->assertNotNull( $this->link_repository->find_by_id( $unused->get_id() ) );
		$this->assertCount( 0, $this->pending_actions() );
	}

	/**
	 * @testdox A run handles 50 links, then queues the next batch from the last id it handled.
	 *
	 * @return void
	 */
	public function test_a_full_batch_queues_the_next_batch(): void {
		$links = array();
		for ( $i = 1; $i <= 51; $i++ ) {
			$links[] = $this->create_done_link( "https://orphan-batch-{$i}.com" );
		}

		( new Orphaned_Link_Clean_Up_Event() )( $links[0]->get_id() - 1 );

		// The first 50 are gone, the 51st is left for the next batch.
		$this->assertNull( $this->link_repository->find_by_id( $links[49]->get_id() ) );
		$this->assertNotNull( $this->link_repository->find_by_id( $links[50]->get_id() ) );

		$pending = $this->pending_actions();
		$this->assertCount( 1, $pending );
		$this->assertSame( array( 'after_id' => $links[49]->get_id() ), $pending[0]->get_args() );
	}

	/**
	 * @testdox When a run finds fewer than 50 links, the next run is queued for tomorrow midnight.
	 *
	 * @return void
	 */
	public function test_the_last_batch_queues_tomorrows_run(): void {
		$unused = $this->create_done_link( 'https://orphan-last-batch.com' );

		( new Orphaned_Link_Clean_Up_Event() )( $unused->get_id() - 1 );

		$pending = $this->pending_actions();
		$this->assertCount( 1, $pending );
		$this->assertSame( array(), $pending[0]->get_args() );
		$this->assertSame( strtotime( 'tomorrow midnight' ), $pending[0]->get_schedule()->get_date()->getTimestamp() );
	}

	/**
	 * @testdox The last batch does not queue tomorrow's run when one is already waiting.
	 *
	 * @return void
	 */
	public function test_the_last_batch_does_not_queue_a_second_run(): void {
		\as_schedule_single_action( strtotime( 'tomorrow midnight' ), Orphaned_Link_Clean_Up_Event::HANDLE );

		( new Orphaned_Link_Clean_Up_Event() )();

		$this->assertCount( 1, $this->pending_actions() );
	}

	/**
	 * @testdox If the clean up fails part way, the error is thrown and tomorrow's run is still queued.
	 *
	 * @return void
	 */
	public function test_a_failed_run_throws_and_still_queues_tomorrows_run(): void {
		// Fail the clean up's own query only.
		add_filter(
			'query',
			function ( string $query ): string {
				if ( false !== strpos( $query, 'archive_process = ' ) ) {
					throw new \Exception( 'Query failed' );
				}
				return $query;
			}
		);

		try {
			( new Orphaned_Link_Clean_Up_Event() )();
			$this->fail( 'A failed run should throw.' );
		} catch ( \RuntimeException $e ) {
			$this->assertStringContainsString( 'Query failed', $e->getMessage() );
		}

		remove_all_filters( 'query' );

		$pending = $this->pending_actions();
		$this->assertCount( 1, $pending );
		$this->assertSame( strtotime( 'tomorrow midnight' ), $pending[0]->get_schedule()->get_date()->getTimestamp() );
	}

	/**
	 * @testdox The daily run is queued for tomorrow midnight when the setting is on, once only.
	 *
	 * @return void
	 */
	public function test_add_to_action_scheduler_queues_once_when_on(): void {
		Orphaned_Link_Clean_Up_Event::add_to_action_scheduler();
		Orphaned_Link_Clean_Up_Event::add_to_action_scheduler();

		$pending = $this->pending_actions();
		$this->assertCount( 1, $pending );
		$this->assertSame( strtotime( 'tomorrow midnight' ), $pending[0]->get_schedule()->get_date()->getTimestamp() );
	}

	/**
	 * @testdox The daily run is not queued when the setting is off.
	 *
	 * @return void
	 */
	public function test_add_to_action_scheduler_does_nothing_when_off(): void {
		update_option( Settings::CLEAN_UP_ORPHANED_LINKS, false );

		Orphaned_Link_Clean_Up_Event::add_to_action_scheduler();

		$this->assertCount( 0, $this->pending_actions() );
	}

	/**
	 * @testdox With the setting on, init adds the daily run to Action Scheduler, and its hook removes unused links.
	 *
	 * @return void
	 */
	public function test_init_queues_the_daily_run_and_firing_it_removes_unused_links(): void {
		add_filter( 'wp_doing_cron', '__return_true' );
		remove_all_actions( 'init' );
		remove_all_actions( Orphaned_Link_Clean_Up_Event::HANDLE );
		( new Event_Controller() )->initialize();

		$unused = $this->create_done_link( 'https://orphan-daily-unused.com' );
		$used   = $this->create_done_link( 'https://orphan-daily-used.com', self::factory()->post->create() );

		do_action( 'init' );

		// It is added.
		$this->assertCount( 1, $this->pending_actions() );

		// And the hook does the clean up.
		do_action( Orphaned_Link_Clean_Up_Event::HANDLE );

		$this->assertNull( $this->link_repository->find_by_id( $unused->get_id() ) );
		$this->assertNotNull( $this->link_repository->find_by_id( $used->get_id() ) );
	}

	/**
	 * @testdox With the setting off, init does not add the daily run to Action Scheduler.
	 *
	 * @return void
	 */
	public function test_init_does_not_queue_the_daily_run_when_off(): void {
		update_option( Settings::CLEAN_UP_ORPHANED_LINKS, false );
		add_filter( 'wp_doing_cron', '__return_true' );
		remove_all_actions( 'init' );
		( new Event_Controller() )->initialize();

		do_action( 'init' );

		$this->assertCount( 0, $this->pending_actions() );
	}

	/**
	 * @testdox A next batch is added to Action Scheduler, and its hook only handles links after the id it is given.
	 *
	 * @return void
	 */
	public function test_firing_a_queued_batch_starts_after_its_id(): void {
		remove_all_actions( Orphaned_Link_Clean_Up_Event::HANDLE );
		( new Event_Controller() )->initialize();

		$before = $this->create_done_link( 'https://orphan-queued-before.com' );
		$after  = $this->create_done_link( 'https://orphan-queued-after.com' );

		\as_enqueue_async_action( Orphaned_Link_Clean_Up_Event::HANDLE, array( 'after_id' => $before->get_id() ) );

		// It is added.
		$this->assertCount( 1, $this->pending_actions() );

		// And the hook only handles links after the id it is given.
		do_action( Orphaned_Link_Clean_Up_Event::HANDLE, $before->get_id() );

		$this->assertNotNull( $this->link_repository->find_by_id( $before->get_id() ) );
		$this->assertNull( $this->link_repository->find_by_id( $after->get_id() ) );
	}
}
