<?php

/**
 * Create and access the events.
 *
 * @since      1.0.0
 * @version    1.0.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Event;

use ActionScheduler;
use ActionScheduler_Store;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Log;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Event\Event;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Reports;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report_Repository;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Analyzer\Runner\Scheduled_Runner;

defined( 'ABSPATH' ) || exit;

/**
 * Event Repository
 */
class Events {

	public const EVENT_GROUP = 'wpcomsp_wlf';

	/**
	 * Access to the reports
	 *
	 * @since 1.0.0
	 *
	 * @var Reports
	 */
	private Reports $reports;

	/**
	 * The report repository.
	 *
	 * @since 1.1.0
	 *
	 * @var Report_Repository
	 */
	private Report_Repository $report_repository;

	/**
	 * Creates a new instance of the Events repository.
	 *
	 * @since  1.0.0
	 */
	public function __construct() {
		$this->reports           = new Reports();
		$this->report_repository = new Report_Repository();
	}

	/**
	 * Register the hooks.
	 *
	 * @since  1.0.0
	 *
	 * @return void
	 */
	public function initialize(): void {
		add_action( Settings::RUNNER_EVENT, new Scheduled_Runner(), 10, 1 );
	}

	/**
	 * Creates a new event.
	 *
	 * @since  1.0.0
	 *
	 * @param string[]  $post_types   The post types to scan.
	 * @param integer[] $http_codes   The HTTP codes to scan for.
	 * @param integer[] $ignore_posts The posts to ignore.
	 * @param boolean   $ignore_cache Should the cache be ignored?.
	 * @param integer   $user_id      The user ID.
	 * @param integer   $blog_id      The blog ID.
	 * @param boolean   $fix_links    Should the links be fixed?.
	 *
	 * @return integer The event id.
	 */
	public function create_event(
		array $post_types,
		array $http_codes,
		array $ignore_posts,
		bool $ignore_cache,
		int $user_id,
		int $blog_id,
		bool $fix_links = false
	): int {

		// Allow for filters to add additional posts to ignore.
		$ignore_posts = apply_filters( 'wpcomsp_wayback_link_fixer_ignore_posts', $ignore_posts );

		$initial_blog = get_current_blog_id();

		switch_to_blog( $blog_id );

		$post_ids = $this->get_post_ids( $post_types, $ignore_posts );

		$args = array(
			'posts'        => join( ', ', $post_ids ),
			'processed'    => '',
			'ignore_cache' => $ignore_cache,
			'http_codes'   => join( ', ', $http_codes ),
			'fix_links'    => $fix_links,
		);

		// If we have no posts to scan, throw an error.
		if ( empty( $args['posts'] ) ) {
			throw new \Exception( esc_html__( 'No posts to process', 'wpcomsp_wayback_link_fixer' ), 1 );
		}

		// Check if the event already exists.
		if ( as_next_scheduled_action( Settings::RUNNER_EVENT, $args, self::EVENT_GROUP ) ) {
			throw new \Exception( esc_html__( 'Event already exists', 'wpcomsp_wayback_link_fixer' ), 1 );
		}

		// Create the report.
		$report = $this->reports->add_description_to_report(
			$this->reports->create_report( $user_id, $blog_id ),
			sprintf(
				// translators: %1$s is the list of post types, %2$s is the list of http codes, %3$s is the list of ignored posts, %4$s is the ignore cache flag, %5$s is the fix links flag.
				__( 'Event created for all posts from the %1$s post types, with %2$s http codes, ignoring posts [%3$s], %4$s and %5$s', 'wpcomsp_wayback_link_fixer' ),
				join( ',', $post_types ),
				join( ',', $http_codes ),
				join( ',', $ignore_posts ),
				$ignore_cache ? __( 'ignoring the cache', 'wpcomsp_wayback_link_fixer' ) : __( 'not ignoring the cache', 'wpcomsp_wayback_link_fixer' ),
				$fix_links ? __( 'fixing links', 'wpcomsp_wayback_link_fixer' ) : __( 'not fixing links', 'wpcomsp_wayback_link_fixer' )
			)
		);

		// Create the event.
		$event = new Event(
			$post_ids,
			$http_codes,
			$ignore_cache,
			$report,
			$fix_links
		);

		// Schedule the event passing a serialized version of the event.
		$event_ref = $this->enqueue_event( $event );

		// Switch back to the initial blog.
		switch_to_blog( $initial_blog );

		return $event_ref;
	}

	/**
	 * Enqueue event.
	 *
	 * @since  1.0.0
	 *
	 * @param Event $event The event to enqueue.
	 *
	 * @return integer The event id.
	 */
	public function enqueue_event( Event $event ): int {
		return \as_enqueue_async_action(
			Settings::RUNNER_EVENT,
			array(
				'event' => \serialize( $event ), //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
				'time'  => time(),
			),
			self::EVENT_GROUP,
			false
		);
	}

	/**
	 * Adds a in progress event to the queue.
	 *
	 * @since 1.1.0
	 *
	 * @param Event $event The event to enqueue.
	 *
	 * @return integer The event id.
	 */
	public function enqueue_in_progress_event( Event $event ): int {

		// Get the report.
		$report = $event->get_report();

		// Add the processed posts to the report.
		$report = $this->reports->add_description_to_report(
			$report,
			sprintf(
				// translators: %1$s is the original description, %2$s is the list of processed posts.
				__( '%1$s. Processed posts: [%2$s]', 'wpcomsp_wayback_link_fixer' ),
				$report->get_description(),
				join( ',', $event->get_processed_post_ids() )
			)
		);

		// Update report.
		$report = $this->report_repository->upsert( $report );

		// Update event with new report.
		$event = $event->update_report( $report );

		return $this->enqueue_event( $event );
	}

	/**
	 * Get the list of all post ids.
	 *
	 * @since  1.0.0
	 *
	 * @param string[]  $post_types   The post types to scan.
	 * @param integer[] $ignore_posts The posts to ignore.
	 *
	 * @return integer[]
	 */
	private function get_post_ids( array $post_types, array $ignore_posts ): array {
		return get_posts(
			array(
				'post_type'      => $post_types,
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'fields'         => 'ids',
				'post__not_in'   => $ignore_posts,
			)
		);
	}

	/**
	 * Get active events.
	 *
	 * @since  1.0.0
	 *
	 * @return array{event: Event, report: Report, logs: Log[]}[]
	 */
	public function get_active_events(): array {
		$events = as_get_scheduled_actions(
			array(
				'hook'     => Settings::RUNNER_EVENT,
				'per_page' => -1,
				'status'   => \ActionScheduler_Store::STATUS_PENDING,
			)
		);
		return $events;
	}

	/**
	 * Get an event based on its ID.
	 *
	 * @since  1.0.0
	 *
	 * @param integer $event_id The event ID.
	 *
	 * @return \ActionScheduler_Action|null
	 */
	public function get_event( int $event_id ): ?\ActionScheduler_Action {
		$event = \ActionScheduler::store()->fetch_action( $event_id );
		// If the event hook is empty, return null.
		if ( empty( $event->get_hook() ) ) {
			return null;
		}
		return $event;
	}

	/**
	 * Finalize an event.
	 *
	 * @since  1.0.0
	 *
	 * @param Event $event The event to finalize.
	 *
	 * @return void
	 */
	public function finalize_event( Event $event ): void {
		// Get the report.
		$report = $event->get_report();

		// Add the processed posts to the report.
		$report = $this->reports->add_description_to_report(
			$report,
			sprintf(
				// translators: %1$s is the original description, %2$s is the list of processed posts.
				__( '%1$s. Processed posts: [%2$s]', 'wpcomsp_wayback_link_fixer' ),
				$report->get_description(),
				join( ',', $event->get_processed_post_ids() )
			)
		);

		// Mark the report as completed.
		$report = $this->reports->mark_report_as_completed( $report );
	}
}
