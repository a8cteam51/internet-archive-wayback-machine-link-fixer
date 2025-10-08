<?php

/**
 * Event for checking if the archive creation is still in progress.
 *
 * @package Wayback_Link_Fixer
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Event;

use Exception;
use Throwable;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link_Repository;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Wayback_Machine_Client;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Wayback_Machine_Service;

defined( 'ABSPATH' ) || exit;

/**
 * Check Archive Creation Event class.
 */
class Check_Snapshot_Status_Event {

	public const HANDLE = 'iawmlf_check_snapshot_status';

	/**
	 * Link repository.
	 *
	 * @var Link_Repository
	 */
	private $link_repository;

	/**
	 * Wayback Machine Client.
	 *
	 * @var Wayback_Machine_Client
	 */
	private $wayback_machine;

	/**
	 * Attempts to check.
	 *
	 * @var integer
	 */
	private $attempts = 0;
	/**
	 * Create instance of the class.
	 */
	public function __construct() {
		$this->attempts = apply_filters( 'iawmlf_check_snapshot_status_attempts', 3 );
	}

	/**
	 * Setup the event.
	 *
	 * @return void
	 */
	public function setup(): void {
		$this->link_repository = new Link_Repository();
		$this->wayback_machine = new Wayback_Machine_Service();
	}

	/**
	 * Get the interval between checks.
	 *
	 * @return integer
	 */
	public static function get_interval(): int {
		return absint( apply_filters( 'iawmlf_check_snapshot_status_interval', 10 * \MINUTE_IN_SECONDS ) );
	}

	/**
	 * Add event to the queue.
	 *
	 * @param integer      $link_id The link ID.
	 * @param string       $job_id  The job ID.
	 * @param integer      $attempt The attempt number.
	 * @param integer|null $delay   The delay in seconds.
	 *
	 * @return void
	 */
	public static function add_to_queue( int $link_id, string $job_id, int $attempt = 0, ?int $delay = null ): void {

		$time = ! is_int( $delay ) || 0 === $delay
			? time() + self::get_interval()
			: time() + $delay;

		// Add the event to the queue.
		as_schedule_single_action(
			$time,
			self::HANDLE,
			array(
				'link_id' => $link_id,
				'job_id'  => $job_id,
				'attempt' => $attempt,
			)
		);
	}

	/**
	 * Marks a link as done from its ID.
	 *
	 * @param integer $link_id The link ID.
	 *
	 * @return void
	 */
	private function mark_as_done( int $link_id ): void {
		$link = $this->link_repository->find_by_id( $link_id );
		// if we dont have a link, return early.
		if ( ! $link ) {
			return;
		}
		// Set the link as done.
		$link = $link->set_done();
		// Update the link in the repository.
		$this->link_repository->upsert( $link );
	}

	/**
	 * Mark a link as pending from its ID.
	 *
	 * @param integer $link_id The link Id.
	 *
	 * @return void
	 */
	private function mark_as_pending( int $link_id ): void {
		$link = $this->link_repository->find_by_id( $link_id );
		// if we dont have a link, return early.
		if ( ! $link ) {
			return;
		}

		// If the link is done, bail.
		if ( $link->is_processed() ) {
			return;
		}

		// Set the link as pending.
		$link = $link->set_pending();
		// Update the link in the repository.
		$this->link_repository->upsert( $link );
	}



	/**
	 * Handle the event.
	 *
	 * @param integer $link_id The link ID.
	 * @param string  $job_id  The job ID.
	 * @param integer $attempt The attempt number.
	 *
	 * @return void
	 */
	public function __invoke( int $link_id, string $job_id, int $attempt = 0 ): void {
		$this->setup();

		// If the attempt is equal to or greater than the max attempts, return early.
		if ( $attempt >= $this->attempts ) {
			// If not marked as done, update the process status.
			$this->mark_as_done( $link_id );
			throw new Exception( esc_html( "Max attempts reached for id:{$link_id}" ) );
		}

		// If the service is offline, try again later.
		if ( ! $this->wayback_machine->is_online() ) {
			$this->mark_as_pending( $link_id );
			self::add_to_queue( $link_id, $job_id, $attempt, HOUR_IN_SECONDS );
			throw new Exception( esc_html( 'Service is offline, trying again in 1 hour.' ) );
		}

		try {
			// Find the link based on its id.
			$link = $this->link_repository->find_by_id( $link_id );
		} catch ( Throwable $th ) {
			$this->mark_as_pending( $link_id );
			self::add_to_queue( $link_id, $job_id, $attempt + 1 );
			throw new Exception(
				esc_html(
					sprintf(
						'Error finding link id: %d, error: %s',
						absint( $link_id ),
						esc_html( $th->getMessage() )
					)
				)
			);
		}

		// If we dont have a link, throw an exception.
		if ( ! $link ) {
			throw new Exception( esc_html( "Link not found for id:{$link_id}" ) );
		}

		try {
			// Get the status of the archive.
			$status = $this->wayback_machine->get_snapshot_status( $job_id );
		} catch ( Throwable $th ) {
			$this->mark_as_pending( $link_id );
			self::add_to_queue( $link_id, $job_id, $attempt + 1 );
			throw new Exception(
				esc_html(
					sprintf(
						'Error getting status for link id: %d, error: %s',
						absint( $link_id ),
						esc_html( $th->getMessage() )
					)
				)
			);
		}

		// If status is error, throw exception with error code.
		if ( 'error' === $status['status'] ) {
			// Update the link with the error message.
			$link = $link->set_message( esc_html( $status['message'] ) );

			// If the status has a 'status_ext' key, set the link as excluded.
			if ( isset( $status['status_ext'] ) && 'error:no-access' === $status['status_ext'] ) {
				$link = $link->set_excluded();
				$link->set_done();
			} else {
				// Mark the link as pending, if not already done..
				self::add_to_queue( $link_id, $job_id, $attempt + 1 );
				if ( ! $link->is_processed() ) {
					$link = $link->set_pending();
				}
			}

			$this->link_repository->upsert( $link );

			throw new Exception(
				esc_html(
					sprintf(
						'Error getting status for link id: %d, error: %s',
						absint( $link_id ),
						esc_html( $status['message'] )
					)
				)
			);
		}

		// If the status is success, create Update_Archive_URL_Event.
		if ( 'success' === $status['status'] ) {
			$this->mark_as_pending( $link_id );
			Update_Archive_URL_Event::add_to_queue( $link_id );
			return;
		}

		// Assume pending if not success or error
		$this->mark_as_pending( $link_id );
		self::add_to_queue( $link_id, $job_id, $attempt + 1 );
	}
}
