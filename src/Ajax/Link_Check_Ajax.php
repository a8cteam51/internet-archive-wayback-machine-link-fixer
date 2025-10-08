<?php

/**
 * Class which handles the AJAX requests for the link checker.
 *
 * @since   1.2.0
 */

declare( strict_types = 1 );

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Ajax;

use DateTime;
use Exception;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link_Repository;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Link_Checker_Client;

defined( 'ABSPATH' ) || exit;

/**
 * Link_Check_Ajax
 */
class Link_Check_Ajax {

	/**
	 * The action name for the AJAX request.
	 */
	public const ACTION = 'iawmlf_link_check_ajax';

	/**
	 * The nonce name for the AJAX request.
	 */
	public const NONCE = 'iawmlf_link_check_nonce';

	/**
	 * Acces to the Link Repository.
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
	 * Register the ajax action.
	 *
	 * @return void
	 */
	public static function register_ajax_call(): void {
		$handler = static function () {
			( new self() )->__invoke();
		};

		add_action( 'wp_ajax_' . self::ACTION, $handler );
		add_action( 'wp_ajax_nopriv_' . self::ACTION, $handler );
	}

	/**
	 * Setup a new instance of the Link_Check_Ajax.
	 *
	 * @return void
	 */
	private function setup(): void {
		$this->link_repository = new Link_Repository();
		$this->link_checker    = wpcomsp_wayback_link_fixer_get_link_checker_client();
	}

	/**
	 * The invocation method for the AJAX request.
	 *
	 * @return void
	 */
	public function __invoke(): void {
		// Setup the dependencies.
		$this->setup();

		// Validate the nonce.
		try {
			$this->validate_request();
		} catch ( Exception $e ) {
			$this->send_error( $e->getMessage(), 403 );
		}

		// Find the link.
		$link = $this->link_repository->find_by_url( untrailingslashit( sanitize_text_field( wp_unslash( $_POST['link'] ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, Checked above.

		// If the link does not exist, send an error.
		if ( null === $link ) {
			$this->send_error( 'Link not found.', 404 );
		}

		// If the link needs to be checked, check it.
		if ( $this->needs_check( $link ) ) {
			$this->check_link( $link );
		} else {
			$this->send_success(
				array(
					'updated' => false,
					'valid'   => $link->is_valid(),
					'link'    => $link,
				)
			);
		}
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
		if ( wpcomsp_wayback_link_fixer_is_archive_link( $link->get_href() ) ) {
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
	 * @return void
	 */
	private function check_link( Link $link ): void {

		// Get the current status.
		try {
			$status = $this->link_checker->check_single( $link->get_href() );
		} catch ( Exception $e ) {
			$this->send_error( $e->getMessage(), 500 );
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
		$this->send_success(
			array(
				'updated' => true,
				'valid'   => $valid,
				'link'    => $link,
			)
		);
	}

	/**
	 * Validates the request.
	 *
	 * @return void
	 *
	 * @throws Exception If the request is invalid.
	 */
	private function validate_request(): void {

		// Check we have the link id in the request.
		if ( ! isset( $_POST['link'] ) ) {
			throw new Exception( 'Link not set in request.' );
		}

		// Check the nonce is set in the request.
		if ( ! isset( $_POST['nonce'] ) ) {
			throw new Exception( 'Nonce not set in request.' );
		}

		// Verify the nonce.
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), self::ACTION ) ) {
			throw new Exception( 'Invalid nonce.' );
		}
	}

	/**
	 * Sends an error response.
	 *
	 * @param string  $message The error message.
	 * @param integer $status  The HTTP status code.
	 *
	 * @return void
	 */
	private function send_error( string $message, int $status = 500 ): void {
		wp_send_json_error( $message, $status );
	}

	/**
	 * Sends a success response.
	 *
	 * @param mixed $data The data to send.
	 *
	 * @return void
	 */
	private function send_success( $data ): void {
		wp_send_json_success( $data );
	}
}
