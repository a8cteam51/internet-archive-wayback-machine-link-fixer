<?php

/**
 * Event for checking if the validator is still in progress.
 *
 * @package Wayback_Link_Fixer
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Event;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link_Repository;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Wayback_Machine_Client;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Wayback_Machine_Service;

/**
 * Check Validator Creation Event class.
 */
class Check_Validator_Status {

	public const HANDLE = 'wlf_check_validator_status';

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
		$this->attempts = \apply_filters( 'wlf_check_validator_status_attempts', 3 );
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
		return absint( \apply_filters( 'wlf_check_validator_status_interval', 2 * \MINUTE_IN_SECONDS ) );
	}

	/**
	 * Add event to the queue.
	 *
	 * @param integer $link_id The link ID.
	 * @param string  $job_id  The job ID.
	 * @param integer $attempt The attempt number.
	 *
	 * @return void
	 */
	public static function add_to_queue( int $link_id, string $job_id, int $attempt = 0 ): void {
		// Get the time to call this.
		$time = \time() + self::get_interval();

		// Add the event to the queue.
		\as_schedule_single_action(
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
			throw new \Exception( esc_html( "Max attempts reached for id:{$link_id}" ) );
		}

		try {
			// Find the link based on its id.
			$link = $this->link_repository->find_by_id( $link_id );
		} catch ( \Throwable $th ) {
			self::add_to_queue( $link_id, $job_id, $attempt + 1 );
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

		// If we dont have a link, throw an exception.
		if ( ! $link ) {
			throw new \Exception( esc_html( "Link not found for id:{$link_id}" ) );
		}

		try {
			// Get the status of the archive.
			$status = $this->wayback_machine->get_snapshot_status( $job_id );
		} catch ( \Throwable $th ) {
			self::add_to_queue( $link_id, $job_id, $attempt + 1 );
			throw new \Exception(
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
				$link = $link->set_excluded()->set_broken( false );
			}

			$this->link_repository->upsert( $link );

			throw new \Exception(
				esc_html(
					sprintf(
						'Link id: %d excluded due to status, error: %s',
						absint( $link_id ),
						esc_html( $status['message'] )
					)
				)
			);
		}

		// If status is success, set the link as not broken.
		if ( 'success' === $status['status'] ) {
			$link = $link->set_excluded( false )->set_broken( false );
			$this->link_repository->upsert( $link );

			return;
		}

		// Assume pendning if not success or error
		self::add_to_queue( $link_id, $job_id, $attempt + 1 );
	}
}
