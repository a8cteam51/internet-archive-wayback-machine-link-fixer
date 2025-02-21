<?php

/**
 * Action scheduler event for checking if a link allows link checking.
 *
 * This is fired after an archived link has been found.
 *
 * @since 1.3.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Event;

use Exception;
use Throwable;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link_Repository;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Wayback_Machine_Service;

defined( 'ABSPATH' ) || exit;

/**
 * Link Access Validator Event class.
 */
class Link_Access_Validator_Event {

	public const HANDLE = 'wlf_link_access_validator';

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
	 * Add a delay to the event.
	 *
	 * @param integer $link_id The link id.
	 * @param integer $delay   The delay in seconds.
	 *
	 * @return void
	 */
	public static function add_to_queue_with_delay( int $link_id, int $delay = \HOUR_IN_SECONDS ): void {
		$time_to_run = time() + $delay;

		as_schedule_single_action(
			$time_to_run,
			self::HANDLE,
			array(
				'link_id' => $link_id,
			)
		);
	}

	/**
	 * Invoke the event.
	 *
	 * @param integer $link_id The link id.
	 *
	 * @return void
	 */
	public function __invoke( int $link_id ): void {
		$this->setup();

		try {
			// Find the link based on its id.
			$link = $this->link_repository->find_by_id( $link_id );
		} catch ( Throwable $th ) {
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

		// If the service is offline, we can't check the link.
		if ( ! $this->wayback_machine->is_online() ) {
			self::add_to_queue_with_delay( $link_id );
			throw new Exception( esc_html( 'Service is offline, trying again in 1 hour.' ) );
		}

		// If we have no link, throw an error.
		if ( null === $link ) {
			throw new Exception( esc_html( 'Link not found with id ' . $link_id ) ); //
		}

		$job_id = $this->wayback_machine->create_snapshot( $link->get_href() );

		// If we dont have a job id, throw an error.
		if ( null === $job_id ) {
			throw new Exception( esc_html( 'Error creating link validation process for link ' . $link_id ) );
		}

		// Initiate the checker.
		Check_Validator_Status::add_to_queue( $link_id, $job_id );
	}
}
