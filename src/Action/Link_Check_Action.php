<?php

/**
 * Action for checking a links status.
 *
 * @package Link_Checker
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Action;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link_Repository;

/**
 * Link_Check_Action
 */
class Link_Check_Action {

	/**
	 * Link repository.
	 *
	 * @var Link_Repository
	 */
	private $link_repository;

	/**
	 * Link checker client.
	 *
	 * @var Link_Checker_Client
	 */
	private $link_checker;

	/**
	 * Create instance of the class.
	 */
	public function __construct() {
		$this->link_repository = new Link_Repository();
		$this->link_checker    = wpcomsp_wayback_link_fixer_get_link_checker_client();
	}

	/**
	 * Check a link based on its ID.
	 *
	 * @param integer $link_id The ID of the link to check.
	 *
	 * @return array{link: Link|null, checked: bool, valid: bool}
	 */
	public function check_link( int $link_id ): array {
		$link = $this->link_repository->find_by_id( $link_id );

		if ( ! $link ) {
			return array(
				'link'    => null,
				'checked' => false,
				'valid'   => false,
			);
		}

		// If the link is an internet archive link, we don't need to check it.
		if ( wpcomsp_wayback_link_fixer_is_archive_link( $link->get_href() ) ) {
			return array(
				'link'    => $link,
				'checked' => false,
				'valid'   => $link->is_valid(),
			);
		}

		// If a link is set as excluded, it cant be checked.
		if ( $link->is_excluded() ) {
			return array(
				'link'    => $link,
				'checked' => false,
				'valid'   => true,
			);
		}

		// Get the current status.
		try {
			$status = $this->link_checker->check_single( $link->get_href() );
		} catch ( \Exception $e ) {
			return array(
				'link'    => $link,
				'checked' => false,
				'valid'   => false,
			);
		}

		// Add the status to the link.
		$link->add_check( $status, gmdate( 'Y-m-d H:i:s' ) );

		// Validate the link.
		$valid = $link->is_valid();

		// If the link is set to be excluded, set as valid.
		if ( $link->is_excluded() ) {
			$link->set_valid( true );
			$valid = true;
		}

		// Update the link.
		$link = $this->link_repository->upsert( $link );

		return array(
			'link'    => $link,
			'checked' => true,
			'valid'   => $valid,
		);
	}
}
