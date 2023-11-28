<?php

/**
 * The Action Scheduler Runner.
 *
 * @since      1.0.0
 * @version    1.0.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Analyzer\Runner;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Event\Event;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Event\Events;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Runner\Runner;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Reports;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Action Scheduler Runner
 */
class Scheduled_Runner {

	/**
	 * The Report Repository.
	 *
	 * @since 1.0.0
	 *
	 * @var Reports
	 */
	private Reports $reports;

	/**
	 * The Events Repository.
	 *
	 * @since 1.0.0
	 *
	 * @var Events
	 */
	private Events $events;

	/**
	 * Creates a new instance of the Action Scheduler Runner.
	 *
	 * @since  1.0.0
	 */
	public function __construct() {
	}

	/**
	 * The invocable method for the Action Scheduler.
	 *
	 * @since  1.0.0
	 *
	 * @param string $event The Serialized event.
	 *
	 * @return void
	 */
	public function __invoke( string $event ): void {

		// Get the event from the arguments.
		try {
			$event = unserialize( $event ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
		} catch ( \Throwable $th ) {
			throw new \Exception( 'Malformed event passed as args' );
		}
		if ( ! $event instanceof Event ) {
			throw new \Exception( 'The event passed to the runner is not an instance of Event.' . esc_attr( $event ) );
		}

		// Populate the properties.
		$this->reports = new Reports();
		$this->events  = new Events();

		// Mark the report as in progress.
		$event->update_report(
			$this->reports->mark_report_as_in_progress( $event->get_report() )
		);

		// Process the next batch.
		$wlf_batch_size = Settings::get_posts_per_batch();
		for ( $wlf_batch_count = 0; $wlf_batch_count < $wlf_batch_size; $wlf_batch_count++ ) {
			$event = $this->process_next_post( $event );
		}

		// If we have more events, add again.
		if ( $event->has_more_events() ) {
			$this->events->enqueue_event( $event );
		} else {
			$this->events->finalize_event( $event );
		}
	}

	/**
	 * Process the next batch of posts.
	 *
	 * @since  1.0.0
	 *
	 * @param Event $event The event to process.
	 *
	 * @return Event
	 */
	private function process_next_post( Event $event ): Event {
		// Get the next post id.
		$post_id = $event->get_next_post_id();

		// If we have no post id, return the event.
		if ( null === $post_id ) {
			return $event;
		}

		// Create the runner.
		$runner = new Runner(
			\get_post( $post_id ),
			$event->ignore_cache(),
			join( ',', $event->get_http_codes() )
		);
		$report = $runner->run( $event->get_report() );

		// Update the events report.
		$event->update_report( $report );

		// Add to processed.
		$event->add_processed_post_id( $post_id );

		return $event;
	}
}
