<?php

/**
 * Action scheduler event for updating the archive URL.
 *
 * This is fired after a snapshot has been initiated and the URL has been updated.
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Event;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link_Repository;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Wayback_Machine_Service;

/**
 * Update Archive URL Event class.
 */
class Update_Archive_URL_Event {

	/**
	 * The event handle.
	 */
	public const HANDLE = 'wpcomsp_wayback_link_fixer_update_archive_url';

	/**
	 * The current attempt.
	 *
	 * @var integer
	 */
	private $attempt = 0;

	/**
	 * The maximum number of attempts.
	 *
	 * @var integer
	 */
	private $max_attempts = 3;

	/**
	 * The Wayback Machine Client.
	 *
	 * @var Wayback_Machine_Service
	 */
	private $wayback_machine;

	/**
	 * Link repository.
	 *
	 * @var Link_Repository
	 */
	private $repository;

	/**
	 * Sets up the events dependencies, but delayed until its called.
	 *
	 * @return void
	 */
	public function setup(): void {
		$this->max_attempts = \apply_filters( 'wlf_max_archive_attempts', $this->max_attempts );

		$this->wayback_machine = new Wayback_Machine_Service();
		$this->repository      = new Link_Repository();
	}

	/**
	 * Add event to the queue.
	 *
	 * @param integer $link_id The link id.
	 * @param integer $attempt The attempt number.
	 * @param integer $delay   The delay in seconds.
	 *
	 * @return void
	 */
	public static function add_to_queue( int $link_id, int $attempt = 0, int $delay = 0 ): void {
		\as_schedule_single_action(
			\time() + $delay,
			self::HANDLE,
			array(
				'link_id' => $link_id,
				'attempt' => $attempt,
			),
			'wayback-link-fixer'
		);
	}

	/**
	 * The invocation of the event.
	 *
	 * @param integer $link_id The link id.
	 * @param integer $attempt The attempt number.
	 *
	 * @throws Exception If the link is not found or the maximum number of attempts has been reached.
	 *
	 * @return void
	 */
	public function __invoke( int $link_id, int $attempt = 1 ): void {

		// Setup
		$this->setup();

		// Find the link
		$link = $this->repository->find_by_id( $link_id );

		// If we dont have a link, then we can't do anything.
		if ( null === $link ) {
			throw new \Exception( esc_attr( "Could not find the link with ID {$link_id}" ), 1 );
		}

		// If we have reached the maximum number of attempts, then mark the link as broken.
		if ( $attempt > $this->max_attempts ) {
			$this->mark_link_broken( $link );
			throw new \Exception( esc_attr( "Reached maximum number of attempts for link with ID {$link_id}" ), 1 );
		}

		// Attempt to get the snapshot url.
		$archive_url = $this->wayback_machine->find_archive( $link->get_href() );

		// If we have an archive URL, then update the link and return.
		if ( null !== $archive_url ) {
			$this->add_archive_url( $link, $archive_url );
			return;
		}

		// If we have no archive URL, then add the event back to the queue.
		self::add_to_queue( $link_id, $attempt + 1, 15 * \MINUTE_IN_SECONDS );
	}

	/**
	 * Mark a link a broken.
	 *
	 * @param Link $link The link to mark as broken.
	 *
	 * @return void
	 */
	private function mark_link_broken( Link $link ): void {
		$link->set_broken();
		$this->repository->upsert( $link );
	}

	/**
	 * Add an archived url to a link.
	 *
	 * @param Link   $link        The link to add the archived url to.
	 * @param string $archive_url The archive url.
	 *
	 * @return void
	 */
	private function add_archive_url( Link $link, string $archive_url ): void {
		$link->set_archived_href( $archive_url );
		$this->repository->upsert( $link );
	}
}
