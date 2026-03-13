<?php

/**
 * REST API endpoint for checking link status.
 *
 * @since   2.0.0
 */

declare( strict_types = 1 );

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Rest;

use DateTime;
use Exception;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link;
use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;
use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link_Repository;
use Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\Link_Checker_Client;

defined( 'ABSPATH' ) || exit;

/**
 * Link_Check_Rest
 */
class Link_Check_Rest {

	/**
	 * The REST API namespace.
	 */
	public const NAMESPACE = 'iawmlf/v1';

	/**
	 * The REST API route.
	 */
	public const ROUTE = '/link-check';

	/**
	 * Access to the Link Repository.
	 *
	 * @var Link_Repository
	 */
	private $link_repository;

	/**
	 * Access to the Link Checker.
	 *
	 * @var Link_Checker_Client
	 */
	private $link_checker;

	/**
	 * Register the REST API routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_request' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'link' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Setup the dependencies.
	 *
	 * @return void
	 */
	private function setup(): void {
		$this->link_repository = new Link_Repository();
		$this->link_checker    = iawmlf_get_link_checker_client();
	}

	/**
	 * Handle the REST API request.
	 *
	 * @param WP_REST_Request $request The REST request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_request( WP_REST_Request $request ) {
		// Setup the dependencies.
		$this->setup();

		// Get the link URL from the request.
		$url = untrailingslashit( $request->get_param( 'link' ) );

		// Find the link.
		$link = $this->link_repository->find_by_url( $url );

		// If the link does not exist, return an error.
		if ( null === $link ) {
			return new WP_Error(
				'link_not_found',
				'Link not found.',
				array( 'status' => 404 )
			);
		}

		return $this->needs_check( $link )
			? $this->check_link( $link )
			: new WP_REST_Response(
				array(
					'updated' => false,
					'valid'   => $link->is_valid(),
					'link'    => $link,
				),
				200
			);
	}

	/**
	 * Check if link needs to be checked.
	 *
	 * @param Link $link The link to check.
	 *
	 * @return boolean
	 */
	private function needs_check( Link $link ): bool {
		$last_check = $link->get_last_check();

		// If we have no check, return true.
		if ( null === $last_check ) {
			return true;
		}

		// If the link is an archive link, return false.
		if ( iawmlf_is_archive_link( $link->get_href() ) ) {
			return false;
		}

		// Check if the last check was more than the duration ago.
		$duration   = Settings::get_link_check_duration();
		$last_check = new DateTime( $last_check['date'] );

		// Get the current time.
		$now = new DateTime();

		// Get the difference in days.
		$diff = $now->diff( $last_check );

		// If the last check was more than the duration set in the settings, return true.
		return $diff->days >= $duration;
	}

	/**
	 * Check the link.
	 *
	 * @param Link $link The link to check.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	private function check_link( Link $link ) {

		// Get the current status.
		try {
			$status = $this->link_checker->check_single( $link->get_href() );
		} catch ( Exception $e ) {
			return new WP_Error(
				'link_check_failed',
				$e->getMessage(),
				array( 'status' => 500 )
			);
		}

		// Add the status to the link.
		$link->add_check( $status, gmdate( 'Y-m-d H:i:s' ) );

		// Validate the link.
		$valid = $link->is_valid();

		// Based on the link status, either set the link as broken or not.
		if ( ! $valid ) {
			$link->set_broken();
		} else {
			$link->set_valid();
		}

		// Update the link.
		$this->link_repository->upsert( $link );

		// Send the success response.
		return new WP_REST_Response(
			array(
				'updated' => true,
				'valid'   => $valid,
				'link'    => $link,
			),
			200
		);
	}
}
