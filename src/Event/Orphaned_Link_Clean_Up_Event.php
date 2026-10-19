<?php

/**
 * Orphaned Link Clean Up Event
 *
 * Runs daily, when enabled, to remove links no post uses any more. Works through the links in batches.
 *
 * @since 1.5.0
 *
 * @package Internet_Archive\Wayback_Machine_Link_Fixer
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Event;

use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;
use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link_Repository;

defined( 'ABSPATH' ) || exit;

/**
 * Orphaned_Link_Clean_Up_Event class.
 */
class Orphaned_Link_Clean_Up_Event {

	public const HANDLE = 'iawmlf_orphaned_link_clean_up';

	/**
	 * The number of links handled per run.
	 */
	public const BATCH_SIZE = 50;

	/**
	 * The link repository.
	 *
	 * @var Link_Repository
	 */
	private $link_repository;

	/**
	 * Adds itself to the action scheduler if enabled and not already scheduled.
	 *
	 * @return void
	 */
	public static function add_to_action_scheduler(): void {
		if ( ! Settings::clean_up_orphaned_links() ) {
			return;
		}

		if ( ! as_next_scheduled_action( self::HANDLE ) ) {
			\as_schedule_single_action( strtotime( 'tomorrow midnight' ), self::HANDLE );
		}
	}

	/**
	 * Sets up the event's dependencies, but delayed until it's called.
	 *
	 * @return void
	 */
	public function setup(): void {
		$this->link_repository = new Link_Repository();
	}

	/**
	 * The invocation of the event.
	 *
	 * @param integer|string $after_id Only links with an id greater than this are handled. A plain do_action() passes ''.
	 *
	 * @return void
	 */
	public function __invoke( $after_id = 0 ): void {
		if ( ! Settings::clean_up_orphaned_links() ) {
			return;
		}

		$after_id = absint( $after_id );

		try {
			$this->setup();

			// Only done links, as a new link is created before it is added to its post's meta.
			$link_ids = $this->link_repository->get_done_link_ids_after( $after_id, self::BATCH_SIZE );
			$used_ids = array_flip( $this->link_repository->get_used_link_ids() );

			foreach ( $link_ids as $link_id ) {
				if ( isset( $used_ids[ $link_id ] ) ) {
					continue;
				}

				$link = $this->link_repository->find_by_id( $link_id );
				if ( null !== $link ) {
					$this->link_repository->delete_link( $link );
				}
			}
		} catch ( \Throwable $e ) {
			self::reschedule();
			throw new \RuntimeException( esc_html( 'Error during orphaned link clean up: ' . $e->getMessage() ) );
		}

		// A full batch may have more after it, so queue the next one now.
		if ( self::BATCH_SIZE === count( $link_ids ) ) {
			\as_enqueue_async_action( self::HANDLE, array( 'after_id' => end( $link_ids ) ) );
			return;
		}

		self::reschedule();
	}

	/**
	 * Queues the next run for tomorrow midnight, unless one is already waiting.
	 *
	 * Only pending runs are checked, as as_next_scheduled_action() also counts this run while it is in progress.
	 *
	 * @return void
	 */
	private static function reschedule(): void {
		$pending = \as_get_scheduled_actions(
			array(
				'hook'     => self::HANDLE,
				'status'   => \ActionScheduler_Store::STATUS_PENDING,
				'per_page' => 1,
			),
			'ids'
		);

		if ( empty( $pending ) ) {
			\as_schedule_single_action( strtotime( 'tomorrow midnight' ), self::HANDLE );
		}
	}
}
