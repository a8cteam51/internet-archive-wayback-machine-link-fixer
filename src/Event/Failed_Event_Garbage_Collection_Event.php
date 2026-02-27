<?php

/**
 * Failed Event Garbage Collection Event
 *
 * Runs daily to clean up failed events that are older than a defined threshold.
 *
 * @since 1.3.5
 *
 * @package Internet_Archive\Wayback_Machine_Link_Fixer
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Event;

use DateTimeImmutable;
use Internet_Archive\Wayback_Machine_Link_Fixer\Util\Action_Scheduler_Garbage_Collection;

defined( 'ABSPATH' ) || exit;

/**
 * Failed_Event_Garbage_Collection_Event class.
 */
class Failed_Event_Garbage_Collection_Event {

	public const HANDLE = 'iawmlf_failed_event_garbage_collection';

	/**
	 * The number of days after which failed events should be deleted.
	 *
	 * @var int
	 */
	private $days_threshold = 7;

	/**
	 * Adds itself to the action scheduler if not already scheduled.
	 *
	 * @return void
	 */
	public static function add_to_action_scheduler(): void {
		if ( ! as_next_scheduled_action( self::HANDLE ) ) {
			$next_midnight = strtotime( 'tomorrow midnight' );
			\as_schedule_single_action( $next_midnight, self::HANDLE );
		}
	}

	/**
	 * Sets up the event's dependencies, but delayed until it's called.
	 *
	 * @return void
	 */
	public function setup(): void {
		$this->days_threshold = apply_filters( 'iawmlf_failed_event_gc_days_threshold', $this->days_threshold );
	}

	/**
	 * The invocation of the event.
	 *
	 * @return void
	 */
	public function __invoke(): void {
		try {
			$this->setup();

			// Compile the threashold datetime.
			$threshold_datetime = DateTimeImmutable::createFromFormat( 'U', (string) time() )->modify( '-' . $this->days_threshold . ' days' );

			// Garbage cleaner
			$cleaner = new Action_Scheduler_Garbage_Collection();
			$cleaner->clean_check_snapshot_status_events( $threshold_datetime );
			$cleaner->clean_create_new_snapshot_events( $threshold_datetime );
			$cleaner->clean_update_archive_url_events( $threshold_datetime );
			$cleaner->clean_check_validator_status_events( $threshold_datetime );
		} catch ( \Throwable $e ) {
			throw new \RuntimeException( 'Error during failed event garbage collection: ' . esc_html( $e->getMessage() ), 0, $e ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		} finally {
			// Reschedule itself for the next day.
			self::add_to_action_scheduler();
		}
	}
}
