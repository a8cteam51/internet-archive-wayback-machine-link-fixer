<?php

/**
 * Rescan a link action.
 *
 * This attempt to get the latest archived version of a link.
 *
 * @since 1.2.0
 *
 * @package WPCOMSpecialProjects\Wayback_Link_Fixer\Action
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Action;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link_Repository;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Wayback_Machine_Service;

/**
 * Link_Latest_Snapshot_Action
 */
class Link_Latest_Snapshot_Action {

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
		$this->repository      = new Link_Repository();
	}

	/**
	 * Rescan a link based on its ID.
	 *
	 * @param integer $link_id The ID of the link to rescan.
	 *
	 * @return array{link: Link|null, found: boolean, updated: boolean, message: string}
	 */
	public function rescan_link( int $link_id ): array {
		$link = $this->repository->find_by_id( $link_id );

		if ( ! $link ) {
			return array(
				'link'    => null,
				'found'   => false,
				'updated' => false,
				'message' => __( 'Link not found.', 'wpcomsp_wayback_link_fixer' ),
			);
		}

		// If the service is offline, we can't check the link.
		if ( ! $this->wayback_machine->is_online() ) {
			return array(
				'link'    => $link,
				'found'   => false,
				'updated' => false,
				'message' => __( 'Service is offline.', 'wpcomsp_wayback_link_fixer' ),
			);
		}

		// If the lik is an internet archive link, we don't need to check it.
		if ( wpcomsp_wayback_link_fixer_is_archive_link( $link->get_href() ) ) {
			return array(
				'link'    => $link,
				'found'   => true,
				'updated' => false,
				'message' => __( 'Link is already an archived link.', 'wpcomsp_wayback_link_fixer' ),
			);
		}

		$archive_url = $this->wayback_machine->find_archive( $link->get_href() );

		// If we don't have an archive URL, add an event and return.
		if ( ! $archive_url ) {
			return array(
				'link'    => $link,
				'found'   => false,
				'updated' => false,
				'message' => __( 'No archive URL found.', 'wpcomsp_wayback_link_fixer' ),
			);
		}

		// If the archive is the same as the archived URL, return.
		if ( $archive_url === $link->get_archived_href() ) {
			return array(
				'link'    => $link,
				'found'   => true,
				'updated' => false,
				'message' => __( 'Archive URL is the same.', 'wpcomsp_wayback_link_fixer' ),
			);
		}

		// Update the links archived URL.
		$link->set_archived_href( $archive_url );

		// Update the link in the database.
		$link = $this->repository->upsert( $link );

		return array(
			'link'    => $link,
			'found'   => true,
			'updated' => true,
			'message' => __( 'Link updated.', 'wpcomsp_wayback_link_fixer' ),
		);
	}
}
