<?php

/**
 * Validate link action
 *
 * This will force a link to checked if a link can be checked.
 *
 * @since 1.3.0
 *
 * @package WPCOMSpecialProjects\Wayback_Link_Fixer\Action
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Action;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link_Repository;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Event\Check_Validator_Status;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Wayback_Machine_Service;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Exception\Exceeded_Snapshot_Limit_Exception;

/**
 * Validate_Link_Action
 */
class Validate_Link_Action {

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
	 * Create new snapshot and update the link.
	 *
	 * @param integer $link_id The ID of the link to update with new snapshot.
	 *
	 * @return array{link: Link|null, job_id:string|null, message: string}
	 */
	public function validate_link( int $link_id ): array {
		$link = $this->link_repository->find_by_id( $link_id );

		if ( ! $link ) {
			return array(
				'link'    => null,
				'job_id'  => null,
				'message' => __( 'Link not found', 'wpcomsp_wayback_link_fixer' ),
			);
		}

		// If we have an internet archive link, we don't need to check it.
		if ( wpcomsp_wayback_link_fixer_is_archive_link( $link->get_href() ) ) {
			return array(
				'link'    => $link,
				'job_id'  => null,
				'message' => __( 'Link is an archive link already', 'wpcomsp_wayback_link_fixer' ),
			);
		}

		// Attempt to create a new snapshot.
		try {
			$job_id = $this->wayback_machine->create_snapshot( $link->get_href() );
		} catch ( \Throwable $th ) {
			// If we have an exceeded snapshot limit exception, return the message.
			if ( $th instanceof Exceeded_Snapshot_Limit_Exception ) {
				return array(
					'link'    => $link,
					'job_id'  => null,
					'message' => __( 'Exceeded snapshot limit', 'wpcomsp_wayback_link_fixer' ),
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
		Check_Validator_Status::add_to_queue( $link_id, $job_id );

		return array(
			'link'    => $link,
			'job_id'  => $job_id,
			'message' => __( 'Validation request created, link will be updated ASAP', 'wpcomsp_wayback_link_fixer' ),
		);
	}
}
