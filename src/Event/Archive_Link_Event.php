<?php

/**
 * The Action Scheduler Event for archiving links.
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Event;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link_Repository;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Client;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Link_Checker\Link_Checker;

/**
 * Archive Link Event class.
 */
class Archive_Link_Event {

	public const HANDLE = 'wpcomsp_wayback_link_fixer_archive_link';

	/**
	 * The Link Checker.
	 *
	 * @var Link_Checker
	 */
	private $link_checker;

	/**
	 * The link repository.
	 *
	 * @var Link_Repository
	 */
	private $link_repository;

	/**
	 * Wayback Machine Client.
	 *
	 * @var Client
	 */
	private $wayback_machine;

	/**
	 * Sets up the events dependencies, but delayed until its called.
	 *
	 * @return void
	 */
	public function setup(): void {
		$this->link_checker    = new Link_Checker();
		$this->link_repository = new Link_Repository();
		$this->wayback_machine = wpcomsp_wayback_link_fixer_get_http_client();
	}

	/**
	 * Adds the event to the queue.
	 *
	 * @param integer $link_id The link id.
	 *
	 * @return void
	 */
	public static function add_to_queue( int $link_id ): void {
		as_enqueue_async_action( self::HANDLE, array( 'link_id' => $link_id ) );
	}

	/**
	 * The invocation of the event.
	 *
	 * @param integer $link_id The link id.
	 *
	 * @return void
	 */
	public function __invoke( int $link_id ): void {

		// Set up
		$this->setup();

		// Look for the link.
		$link = $this->link_repository->find_by_id( $link_id );

		// If the link already has a archived link, return early.
		if ( $link->has_archived_href() && '' !== $link->get_archived_href() ) {
			return;
		}

		// Attempt to get the archived link
		$archive_url = $this->get_archived_link( $link->get_href() );

		// If we don't have an archived link, create a snapshot
		if ( null === $archive_url ) {
			$this->wayback_machine->create_snapshot( $link->get_href() );

			// Add the event to update the the link with the archive URL (this is run later, to allow Wayback time to process the snapshot)
			Update_Archive_URL_Event::add_to_queue( $link_id, 0, 15 * MINUTE_IN_SECONDS );

			// Exit early, we don't want to update the link yet
			return;
		}

		// Update the link with the archive URL
		$link->set_archived_href( $archive_url );

		// Save the link
		$this->link_repository->upsert( $link );
	}

	/**
	 * Get the archived link.
	 *
	 * @param string $url The URL to archive.
	 *
	 * @return string|null The URL of the archived link.
	 */
	private function get_archived_link( string $url ): ?string {
		$archive_url = $this->wayback_machine->get_latest_snapshot( $url );

		// if we dont have an archive url, return null
		if ( null === $archive_url ) {
			return null;
		}

		// return the archive url
		return esc_url( $archive_url['url'] );
	}

	/**
	 * Create a snapshot of the link.
	 *
	 * @param string $url The URL to archive.
	 *
	 * @return void
	 */
	private function create_snapshot( string $url ) {
		$this->link_checker->create_snapshot( $url );
	}
}
