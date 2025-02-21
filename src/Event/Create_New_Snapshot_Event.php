<?php

/**
 * The Action Scheduler Event for creating a new archive.
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Event;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link_Repository;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Event\Check_Snapshot_Status_Event;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Wayback_Machine_Service;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Exception\Exceeded_Snapshot_Limit_Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Archive Link Event class.
 */
class Create_New_Snapshot_Event {

	public const HANDLE = 'wlf_create_new_snapshot';

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
	 * The number of attempts.
	 *
	 * @var integer
	 */
	private $attempt = 0;

	/**
	 * Sets up the events dependencies, but delayed until its called.
	 *
	 * @return void
	 */
	public function setup(): void {
		$this->link_repository = new Link_Repository();
		$this->wayback_machine = new Wayback_Machine_Service();
		$this->attempt         = \apply_filters( 'wlf_create_new_snapshot_attempts', 3 );
	}

	/**
	 * Adds the event to the queue.
	 *
	 * @param integer      $link_id The link id.
	 * @param integer|null $attempt The attempt number.
	 *
	 * @return integer
	 */
	public static function add_to_queue( int $link_id, int $attempt = 0 ): int {
		return as_enqueue_async_action(
			self::HANDLE,
			array(
				'link_id' => $link_id,
				'attempt' => $attempt,
			),
			'wayback-link-fixer'
		);
	}

	/**
	 * Adds a delayed event to the queue.
	 *
	 * @param integer $link_id The link id.
	 * @param integer $attempt The attempt number.
	 * @param integer $delay   The delay in seconds.
	 *
	 * @return integer
	 */
	public static function add_delayed_to_queue( int $link_id, int $attempt, int $delay = 15 * \MINUTE_IN_SECONDS ): int {
		return \as_schedule_single_action(
			time() + $delay,
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
	 * @return void
	 */
	public function __invoke( int $link_id, int $attempt = 0 ): void {

		// Set up
		$this->setup();

		// If the attempt is greater than or equal to the max attempts, return early.
		if ( $attempt >= $this->attempt ) {
			throw new \Exception( esc_html( 'Max attempts reached for link ' . $link_id ) );
		}

		// If the service is offline, try again later in 1 hour.
		if ( ! $this->wayback_machine->is_online() ) {
			self::add_delayed_to_queue( $link_id, $attempt, \HOUR_IN_SECONDS );
			throw new \Exception( esc_html( 'Service is offline, trying again in 1 hour.' ) );
		}

		try {
			// Find the link based on its id.
			$link = $this->link_repository->find_by_id( $link_id );
		} catch ( \Throwable $th ) {
			self::add_to_queue( $link_id, $attempt + 1 );
			throw new \Exception(
				esc_html(
					sprintf(
						'Error finding link id: %d, error: %s',
						absint( $link_id ),
						esc_html( $th->getMessage() )
					)
				)
			);
		}

		// If we have no link, throw an error.
		if ( null === $link ) {
			throw new \Exception( esc_html( 'Link not found with id ' . $link_id ) ); //
		}

		// If the link already has a archived link, return early.
		if ( $link->has_archived_href() && '' !== $link->get_archived_href() ) {
			return;
		}

		// Ensure we are working with the final link, incase its been redirected.
		try {
			$link_url = $this->wayback_machine->get_final_url( $link->get_href() );
		} catch ( \Throwable $th ) {
			self::add_delayed_to_queue( $link_id, $attempt + 1 );
			throw new \Exception(
				esc_html(
					sprintf(
						'Error getting final URL for link id: %d, error: %s',
						absint( $link_id ),
						esc_html( $th->getMessage() )
					)
				)
			);
		}

		// If the link url is different to the href, update the link.
		if ( $link_url !== $link->get_href() ) {
			$link = $this->link_repository->upsert(
				$link->set_redirect_href( $link->get_href() )
			);
		}

		// Attempt to get the archived link
		try {
			$archive_url = $this->get_archived_link( $link_url );
		} catch ( \Throwable $th ) {
			self::add_delayed_to_queue( $link_id, $attempt + 1 );
			throw new \Exception(
				esc_html(
					sprintf(
						'Error getting archive URL for link id: %d, error: %s',
						absint( $link_id ),
						esc_html( $th->getMessage() )
					)
				)
			);
		}

		// If we don't have an archived link, create a snapshot
		if ( null === $archive_url ) {

			// Attempt to create a snapshot
			try {
				$job_id = $this->wayback_machine->create_snapshot( $link_url );
			} catch ( \Throwable $th ) {
				// If this is the last attempt, re throw the error.
				if ( $th instanceof Exceeded_Snapshot_Limit_Exception
				|| $attempt >= $this->attempt
				) {
					throw $th;
				}

				self::add_delayed_to_queue( $link_id, $attempt + 1 );
				return;
			}

			// Add check snapshot status event.
			Check_Snapshot_Status_Event::add_to_queue( $link_id, $job_id );
			return;
		}

		// Update the link with the archive URL
		$link->set_archived_href( $archive_url );

		// If the link_url is not the same as the href, set the redirect href
		if ( $link_url !== $link->get_href() ) {
			$link->set_redirect_href( $link_url );
		}

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
		return $archive_url['url'] ?? '';
	}
}
