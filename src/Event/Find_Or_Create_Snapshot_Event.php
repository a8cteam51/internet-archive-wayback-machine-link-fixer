<?php
/**
 * Attempts to either find or create a snapshot for a given URL and date.
 *
 * @since 1.2.0
 *
 * @package Wayback_Machine
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Event;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link_Repository;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Wayback_Machine_Service;

/**
 * Find or Create Snapshot Event class.
 */
class Find_Or_Create_Snapshot_Event {

	public const HANDLE = 'wlf_find_or_create_snapshot';

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
		\as_enqueue_async_action(
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
			throw new \Exception( esc_html( 'Link not found with id ' . $link_id ) ); //
		}

		// If the link is an archive.org link, add message and throw error.
		if ( wpcomsp_wayback_link_fixer_is_archive_link( $link->get_href() ) ) {
			$link->set_message( esc_html( 'Already an Internet Archive Snapshot.' ) );
			$this->link_repository->upsert( $link );
			throw new \Exception( esc_html( 'Already an Internet Archive Snapshot.' ) );
		}

		// Get the links latest snapshot.
		$snapshot = $this->wayback_machine->get_latest_snapshot( wpcomsp_wayback_link_fixer_normalize_url( $link->get_href() ) );

		// If we have no snapshot, create one.
		if ( null === $snapshot ) {
			Create_New_Snapshot_Event::add_to_queue( $link_id );
			return;
		}

		$link->set_archived_href( $snapshot['url'] );

		// Get the current link status.
		$status = $this->wayback_machine->check_single( wpcomsp_wayback_link_fixer_normalize_url( $link->get_href() ) );

		// Add to the link.
		$link->add_check( $status );

		// Update the link.
		$this->link_repository->upsert( $link );

		// If we have a 403 status, add to the validator queue.
		if ( 403 === $status ) {
			Link_Access_Validator_Event::add_to_queue( $link_id );
		}
	}
}
