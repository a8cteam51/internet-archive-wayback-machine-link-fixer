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
		if ( $this->is_archive_url( $link->get_href() ) ) {
			$link->set_message( esc_html( 'Already an Internet Archive Snapshot.' ) );
			$this->link_repository->upsert( $link );
			throw new \Exception( esc_html( 'Already an Internet Archive Snapshot.' ) );
		}

		// Get the links latest snapshot.
		$snapshot = $this->wayback_machine->get_latest_snapshot( $link->get_href() );

		// If we have no snapshot, create one.
		if ( null === $snapshot ) {
			Create_New_Snapshot_Event::add_to_queue( $link_id );
			return;
		}

		$link->set_archived_href( $snapshot['url'] );
		$this->link_repository->upsert( $link );
	}

	/**
	 * Checks if a given url is an archive.org url.
	 *
	 * @param string $url The url to check.
	 *
	 * @return boolean
	 */
	public function is_archive_url( string $url ): bool {
		$urls = array(
			'https://web.archive.org/web/',
			'http://web.archive.org/web/',
		);

		foreach ( $urls as $archive_url ) {
			if ( 0 === strpos( $url, $archive_url ) ) {
				return true;
			}
		}
		return false;
	}
}
