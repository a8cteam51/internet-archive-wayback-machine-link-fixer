<?php

/**
 * Create new snapshot action.
 *
 * This will force create new snapshot for a link and update the archived URL.
 *
 * @since 1.3.0
 *
 * @package Internet_Archive\Wayback_Machine_Link_Fixer\Action
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Action;

use Throwable;
use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link;
use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link_Repository;
use Internet_Archive\Wayback_Machine_Link_Fixer\Event\Check_Snapshot_Status_Event;
use Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\Wayback_Machine_Service;
use Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\Exception\Exceeded_Snapshot_Limit_Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Link_New_Snapshot_Action
 */
class Link_New_Snapshot_Action {

	/**
	 * Link Repository.
	 *
	 * @var Link_Repository
	 */
	private $link_repository;

	/**
	 * Wayback Machine Client.
	 *
	 * @var Wayback_Machine_Service
	 */
	private $wayback_machine;

	/**
	 * Create instance of the class.
	 */
	public function __construct() {
		$this->wayback_machine = new Wayback_Machine_Service();
		$this->link_repository = new Link_Repository();
	}

	/**
	 * Maybe mark this as pending.
	 *
	 * This is used to mark the link as pending if it is not already.
	 *
	 * @param Link $link The link to check.
	 *
	 * @return void
	 */
	public function maybe_mark_as_pending( Link $link ): void {
		if ( ! $link->is_processed() ) {
			$link->set_pending();
			$this->link_repository->upsert( $link );
		}
	}

	/**
	 * Create new snapshot and update the link.
	 *
	 * @param integer $link_id The ID of the link to update with new snapshot.
	 *
	 * @return array{link: Link|null, job_id:string|null, message: string}
	 */
	public function create_new_snapshot( int $link_id ): array {
		$link = $this->link_repository->find_by_id( $link_id );

		if ( ! $link ) {
			return array(
				'link'    => null,
				'job_id'  => null,
				'message' => __( 'Link not found.', 'internet-archive-wayback-machine-link-fixer' ),
			);
		}

		// Maybe mark as pending.
		$this->maybe_mark_as_pending( $link );

		// If the service is offline, we can't check the link.
		if ( ! $this->wayback_machine->is_online() ) {
			return array(
				'link'    => null,
				'job_id'  => null,
				'message' => __( 'Service is offline.', 'internet-archive-wayback-machine-link-fixer' ),
			);
		}

		// If we have an internet archive link, we don't need to check it.
		if ( iawmlf_is_archive_link( $link->get_href() ) ) {
			return array(
				'link'    => $link,
				'job_id'  => null,
				'message' => __( 'This URL is already an archived link.', 'internet-archive-wayback-machine-link-fixer' ),
			);
		}

		// Attempt to create a new snapshot.
		try {
			$job_id = $this->wayback_machine->create_snapshot( $link->get_href() );
		} catch ( Throwable $th ) {
			// If we have an exceeded snapshot limit exception, return the message.
			if ( $th instanceof Exceeded_Snapshot_Limit_Exception ) {
				return array(
					'link'    => $link,
					'job_id'  => null,
					'message' => __( 'Exceeded snapshot limit.', 'internet-archive-wayback-machine-link-fixer' ),
				);
			} else {
				return array(
					'link'    => $link,
					'job_id'  => null,
					'message' => esc_html( $th->getMessage() ),
				);
			}
		}

		// Add the Check Snapshot Event to the queue.
		Check_Snapshot_Status_Event::add_to_queue( $link_id, $job_id, 0, 15 * MINUTE_IN_SECONDS );

		return array(
			'link'    => $link,
			'job_id'  => $job_id,
			'message' => __( 'Snapshot created, Link will be processed soon', 'internet-archive-wayback-machine-link-fixer' ),
		);
	}
}
