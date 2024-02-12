<?php

/**
 * Updates the contents for a Log (post) after a report has been processed.
 *
 * @since      1.1.0
 * @version    1.1.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Ajax;

use WP_Post;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Log;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Link;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Updater\Log_Processor;
use WPCOMSpecialProjects\Wayback_Link_Fixer\CSV\Report_CSV_Generator;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report_Repository;

defined( 'ABSPATH' ) || exit;

/**
 * Late Log Processor AJAX Handler
 */
class Late_Log_Processor_Ajax {

	public const ACTION    = 'wplf_late_log_processor';
	public const NONCE_KEY = 'wplf_late_log_processor_nonce';

	/**
	 * Access to the Report Repository.
	 *
	 * @since 1.0.0
	 *
	 * @var Report_Repository
	 */
	private Report_Repository $report_repository;

	/**
	 * Create instance of Generate_CSV_Ajax.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->report_repository = new Report_Repository();
	}

	/**
	 * Handle the AJAX request.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	public function __invoke() {
		$this->validate_request();
		$this->process_log();
	}

	/**
	 * Validates the request.
	 *
	 * @since 1.1.0
	 */
	private function validate_request(): void {
		if ( ! isset( $_POST['post_id'] ) ) {
			wp_send_json_error( __( 'Missing post_id parameter.', 'wayback-link-fixer' ) );
		}

		if ( ! isset( $_POST['url'] ) ) {
			wp_send_json_error( __( 'Missing url parameter.', 'wayback-link-fixer' ) );
		}

		if ( ! isset( $_POST['new_url'] ) ) {
			wp_send_json_error( __( 'Missing new_url parameter.', 'wayback-link-fixer' ) );
		}

		// Check we have a log id.
		if ( ! isset( $_POST['log_id'] ) ) {
			wp_send_json_error( __( 'Missing log_id parameter.', 'wayback-link-fixer' ) );
		}

		// If new url is empty or the same as the old url, we don't need to do anything.
		if ( empty( $_POST['new_url'] ) || $_POST['url'] === $_POST['new_url'] ) {
			wp_send_json_success( __( 'No changes made.', 'wayback-link-fixer' ) );
		}

		if ( ! isset( $_POST['nonce'] ) ) {
			wp_send_json_error( __( 'Missing nonce parameter.', 'wayback-link-fixer' ) );
		}

		if ( ! wp_verify_nonce( $_POST['nonce'], self::NONCE_KEY ) ) {
			wp_send_json_error( __( 'Invalid nonce.', 'wayback-link-fixer' ) );
		}
	}

	/**
	 * Process the log.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	private function process_log(): void {
		// Get the post_id
		$post_id = \sanitize_text_field( $_POST['post_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, nonce checked above
		$old_url = \sanitize_text_field( $_POST['url'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, nonce checked above
		$new_url = \sanitize_text_field( $_POST['new_url'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, nonce checked above
		$log_id  = \sanitize_text_field( $_POST['log_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, nonce checked above
		var_dump( $post_id, $old_url, $new_url, $log_id );

		// Get the log.
		$log = $this->report_repository->get_log( (int) $log_id );

		// If we don't have a log, we can't do anything.
		if ( ! $log ) {
			wp_send_json_error( __( 'No log found.', 'wayback-link-fixer' ) );
		}

		// Iterate through all links and replace options for all matching links.
		$found = false;
		$links = $log->get_links();

		foreach ( $links as $index => $link ) {
			dump($link);
			// If the link has been fixed, skip
			if ( $link->has_been_updated() ) {
				continue;
			}

			// If the link has the same URL.
			if ( $old_url === $link->get_href() ) {
				$links[ $index ] = $link->add_replacement_options( array( $new_url ) );
				$found           = true;
			}
		}

		// IF we have no found a link, return error.
		if ( false === $found ) {
			wp_send_json_error( __( 'Link not found or previously fixed?', 'wayback-link-fixer' ) );
		}

		// Set the links to the log.
		$log = $log->with_links( $links );

		// Create the Log Processor.
		$processor = new Log_Processor( $log );

		// Check if we can update the content (post might be removed)
		if ( ! $processor->can_update() ) {
			wp_send_json_error( __( 'Can not be fixed, was the post removed', 'wayback-link-fixer' ) );
		}

		// Fix content.
		$updated = $processor->update_content();

		// If we have not updated.
		if ( ! $updated ) {
			wp_send_json_error( __( 'Failed to update post.', 'wayback-link-fixer' ) );
		}

		$updated_log = $processor->get_log();

		// Update the log.
		$this->report_repository->update_log( $updated_log );

		// Send success with new comments.
		\wp_send_json_success(
			array(
				'updatedPost'  => $updated_log,
				'updatedLinks' => $processor->get_processed_links(),
			)
		);
	}
}
