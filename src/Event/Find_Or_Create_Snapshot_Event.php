<?php
/**
 * Attempts to either find or create a snapshot for a given URL and date.
 *
 * @since 1.2.0
 *
 * @package Wayback_Machine
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Event;

use Exception;
use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link_Repository;
use Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\Wayback_Machine_Service;
use Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\Exception\Service_Offline_Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Find or Create Snapshot Event class.
 */
class Find_Or_Create_Snapshot_Event {

	public const HANDLE = 'iawmlf_find_or_create_snapshot';

	/**
	 * The link repository.
	 *
	 * @var Link_Repository
	 */
	private $link_repository;

	/**
	 * The wayback machine service.
	 *
	 * @var Wayback_Machine_Service
	 */
	private $wayback_machine;

	/**
	 * Sets up the events dependencies, but delayed until its called.
	 *
	 * @return void
	 */
	public function setup(): void {
		$this->link_repository = new Link_Repository();
		$this->wayback_machine = new Wayback_Machine_Service();
	}

	/**
	 * Adds the event to the queue.
	 *
	 * @param integer $link_id The link id.
	 *
	 * @return void
	 */
	public static function add_to_queue( int $link_id ): void {
		as_enqueue_async_action(
			self::HANDLE,
			array(
				'link_id' => $link_id,
			)
		);
	}

	/**
	 * Adds the event to the queue with a delay.
	 *
	 * @param integer $link_id The link id.
	 * @param integer $delay   The delay in seconds.
	 *
	 * @return void
	 */
	public static function add_to_queue_with_delay( int $link_id, int $delay ): void {
		$time = time() + $delay;

		as_schedule_single_action(
			$time,
			self::HANDLE,
			array(
				'link_id' => $link_id,
			)
		);
	}

	/**
	 * Runs the event.
	 *
	 * @param integer $link_id The link id.
	 *
	 * @return void
	 */
	public function __invoke( int $link_id ): void {
		$this->setup();

		$link = $this->link_repository->find_by_id( $link_id );

		// If we have no link, throw an error.
		if ( null === $link ) {
			throw new Exception( esc_html( 'Link not found with id ' . $link_id ) ); //
		}

		// If the service is offline, try again later.
		if ( ! $this->wayback_machine->is_online() ) {
			self::add_to_queue_with_delay( $link_id, 1 * \HOUR_IN_SECONDS );
			return;
		}

		// If the link is an archive.org link, add message and throw error.
		if ( iawmlf_is_archive_link( $link->get_href() ) ) {
			$link->set_message( esc_html( 'Already an Internet Archive Snapshot.' ) );
			$this->link_repository->upsert( $link );
			throw new Exception( esc_html( 'Already an Internet Archive Snapshot.' ) );
		}

		// Get the links latest snapshot.
		$snapshot = $this->wayback_machine->get_latest_snapshot( $link->get_href() );

		// If we have no snapshot, create one.
		if ( null === $snapshot ) {
			Create_New_Snapshot_Event::add_to_queue( $link_id );

			// Mark the link as pending, if not marked as done.
			if ( ! $link->is_processed() ) {
				$link->set_pending();
				$this->link_repository->upsert( $link );
			}

			return;
		}

		$link->set_archived_href( $snapshot['url'] );

		// Get the current link status.
		try {
			$status = $this->wayback_machine->check_single( $link->get_href() );
		} catch ( Service_Offline_Exception $e ) {
			// Non-200 from the link checker: treat as offline, reschedule.
			self::add_to_queue_with_delay( $link_id, 1 * \HOUR_IN_SECONDS );
			return;
		}

		// Add to the link.
		$link->add_check( $status );

		// Mark the status as done.
		$link->set_done();

		// Update the link.
		$this->link_repository->upsert( $link );

		// If we have a 403 status, add to the validator queue.
		if ( 403 === $status ) {
			Link_Access_Validator_Event::add_to_queue( $link_id );
		}
	}
}
